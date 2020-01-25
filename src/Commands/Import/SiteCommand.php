<?php

namespace Pantheon\Terminus\Commands\Import;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\API\Site\SiteAwareInterface;
use Pantheon\Terminus\API\Site\SiteAwareTrait;
use Pantheon\Terminus\API\Exceptions\TerminusException;

/**
 * Class SiteCommand
 * @package Pantheon\Terminus\Commands\Import
 */
class SiteCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use WorkflowProcessingTrait;

    /**
     *  Imports a site archive (code, database, and files) to the site.
     *
     * @authorize
     *
     * @command import:site
     * @aliases site:import
     *
     * @param string $site_name Site name
     * @param string $url Publicly accessible URL of the site archive
     *
     * @usage <site_name> <url> Imports the site archive at <url> to <site_name>.
     */
    public function import($site_name, $url)
    {
        list($site, $env) = $this->getSiteEnv($site_name, 'dev');

        $tr = ['site' => $site->getName(), 'env' => $env->getName()];
        if (!$this->confirm('Are you sure you overwrite the code, database and files for {env} on {site}?', $tr)) {
            return;
        }

        try {
            $this->processWorkflow($env->import($url));
        } catch (\Exception $e) {
            if ($e->getMessage() == 'Successfully queued import_site') {
                throw new TerminusException('Site import failed');
            }
            throw $e;
        }
        $this->log()->notice('Imported site onto Pantheon');
    }
}
