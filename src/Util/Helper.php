<?php

namespace HeimrichHannot\IsotopeSubscriptionsBundle\Util;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Model;

class Helper
{
    protected ContaoFramework $framework;

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    public function callModelMethod(string $table, string $method, ...$args)
    {
        if (!($modelClass = $this->framework->getAdapter(Model::class)->getClassFromTable($table))) {
            return null;
        }

        if (null === ($adapter = $this->framework->getAdapter($modelClass))) {
            return null;
        }

        return \call_user_func_array([$adapter, $method], $args);
    }
}