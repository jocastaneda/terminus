<?php

namespace Pantheon\Terminus\Commands\Env;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\API\Exceptions\TerminusException;
use Pantheon\Terminus\API\Site\SiteAwareInterface;
use Pantheon\Terminus\API\Site\SiteAwareTrait;

/**
 * Class DeployCommand
 * @package Pantheon\Terminus\Commands\Env
 */
class DeployCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use WorkflowProcessingTrait;

    /**
     * Deploys code to the Test or Live environment.
     * Notes:
     *   - Deploying the Test environment will deploy code from the Dev environment.
     *   - Deploying the Live environment will deploy code from the Test environment.
     *
     * @authorize
     *
     * @command env:deploy
     * @aliases deploy
     *
     * @param string $site_env Site & environment in the format `site-name.env` (only Test or Live environment)
     * @option string $sync-content Clone database/files from Live environment when deploying Test environment
     * @option string $cc [DEPRECATED 2.2.1-dev] Does not work. Meant to clear caches after deploy. Please use env:clear-cache instead.
     * @option string $updatedb Run update.php after deploy (Drupal only)
     * @option string $note Custom deploy log message
     *
     * @throws TerminusException
     *
     * @usage <site>.test Deploy code from <site>'s Dev environment to the Test environment.
     * @usage <site>.live Deploy code from <site>'s Test environment to the Live environment.
     * @usage <site>.test --sync-content Deploy code from <site>'s Dev environment to the Test environment and clone content from the Live environment to the Test environment.
     * @usage <site>.live --updatedb Deploy code from <site>'s Test environment to the Live environment and run Drupal's update.php.
     * @usage <site>.live --note=<message> Deploy code from <site>'s Test environment to the Live environment with the deploy log message <message>.
     */
    public function deploy(
        $site_env,
        $options = ['sync-content' => false, 'note' => 'Deploy from Terminus', 'cc' => false, 'updatedb' => false,]
    ) {
        list($site, $env) = $this->getUnfrozenSiteEnv($site_env, 'dev');

        $annotation = $options['note'];
        if ($env->isInitialized()) {
            if (!$env->hasDeployableCode()) {
                $this->log()->notice('There is nothing to deploy.');
                return;
            }

            $params = [
              'updatedb'    => (integer)$options['updatedb'],
              'annotation'  => $annotation,
            ];
            if ($env->id == 'test' && isset($options['sync-content']) && $options['sync-content']) {
                $live_env = 'live';
                if (!$site->getEnvironments()->get($live_env)->isInitialized()) {
                    throw new TerminusException(
                        "{site}'s {env} environment cannot be cloned because it has not been initialized.",
                        ['site' => $site->getName(), 'env' => $live_env,]
                    );
                }
                $params['clone_files'] = $params['clone_database'] = ['from_environment' => $live_env,];
            }
            $workflow = $env->deploy($params);
        } else {
            $workflow = $env->initializeBindings(compact('annotation'));
        }
        $this->processWorkflow($workflow);
        $this->log()->notice($workflow->getMessage());
    }
}
