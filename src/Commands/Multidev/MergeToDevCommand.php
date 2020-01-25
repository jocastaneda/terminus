<?php

namespace Pantheon\Terminus\Commands\Multidev;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\API\Site\SiteAwareInterface;
use Pantheon\Terminus\API\Site\SiteAwareTrait;

/**
 * Class MergeToDevCommand
 * @package Pantheon\Terminus\Commands\Multidev
 */
class MergeToDevCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use WorkflowProcessingTrait;

    /**
     * Merges code commits from a Multidev environment into the Dev environment.
     *
     * @authorize
     *
     * @command multidev:merge-to-dev
     * @aliases env:merge-to-dev
     *
     * @param string $site_env Site & Multidev environment in the form `site-name.env`
     * @option boolean $updatedb Run update.php afterwards
     *
     * @usage <site>.<multidev> Merges code commits from <site>'s <multidev> environment into the Dev environment.
     * @usage <site>.<multidev> --updatedb Merges code commits from <site>'s <multidev> environment into the Dev environment and runs update.php afterwards.
     */
    public function mergeToDev($site_env, $options = ['updatedb' => false,])
    {
        list($site, $env) = $this->getSiteEnv($site_env);
        $workflow = $site->getEnvironments()->get('dev')->mergeToDev(
            ['from_environment' => $env->id, 'updatedb' => $options['updatedb'],]
        );
        $this->processWorkflow($workflow);
        $this->log()->notice('Merged the {env} environment into dev.', ['env' => $env->id,]);
    }
}
