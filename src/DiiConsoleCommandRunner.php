<?php

namespace Koriym\Dii;

use CConsoleCommandRunner;
use Yii;

class DiiConsoleCommandRunner extends CConsoleCommandRunner
{
    /**
     * {@inheritdoc}
     */
    public function createCommand($name)
    {
        $name = strtolower($name);

        $command = null;
        if (isset($this->commands[$name])) {
            $command = $this->commands[$name];
        } else {
            $commands = array_change_key_case($this->commands);
            if (isset($commands[$name])) {
                $command = $commands[$name];
            }
        }

        if ($command !== null) {
            if (is_string($command)) { // class file path or alias
                if (strpos($command, '/') !== false || strpos($command, '\\') !== false) {
                    $className = substr(basename($command), 0, -4);
                    if (! class_exists($className, false)) {
                        require_once $command;
                    }
                } else { // an alias
                    $className = Yii::import($command);
                }

                return Yii::createComponent(['class' => $className], $name, $this);
            }
            // an array configuration
            return Yii::createComponent($command, $name, $this);
        } elseif ($name === 'help') {
            return new \CHelpCommand('help', $this);
        }

        return null;
    }
}
