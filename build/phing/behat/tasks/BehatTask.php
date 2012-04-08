<?php

    require_once dirname(__FILE__)."/../util/BehatLogUtil.php";

    /**
     * Allows behat to run within PHING
     */
    class BehatTask extends Task
    {
        /**
         * @var string      The path to the features directory
         */
        private $featuresDir = null;

        /**
         * @var string      The features to test
         */
        private $features = null;

        /**
         * @var string      The path to the Behat executable
         */
        private $executable = "behat";

        /**
         * @var array       Behat arguments to use
         */
        private $arguments = null;

        /**
         * @var bool        Whether to use the "--strict" flag
         */
        private $strict = false;

        /**
         * @var string      The path to the behat.yml config file
         */
        private $config = null;

        /**
         * @var string      The path to the html output file
         */
        private $htmlOutput = null;

        public function init()
        {
            $this->project->log(str_repeat("-", 50));
            $this->project->log(str_pad("Starting Behat build", 50, " ", STR_PAD_BOTH));
            $this->project->log(str_repeat("-", 50));
        }

        public function main()
        {
            if(empty($this->featuresDir))
                $this->project->log("[baseDir] property is invalid or not set", Project::MSG_ERR);

            $args = array();
            if($this->strict)
                $args[] = "--strict";

            if(!empty($this->config))
                $args[] = "--config ".$this->escapePath($this->config);

            $args = array_merge($args, $this->addLogging());

            if(!empty($this->arguments))
                $args = array_merge($args, $this->arguments->getArguments());

            $startTime = microtime(true);
            $this->callBehat(implode(" ", $args));
            $time = number_format(microtime(true) - $startTime, 4);

            $output = trim($this->project->getProperty("behat.output"));

            if(!empty($output))
            {
                throw new BuildException(new Exception($output));
            }

            try
            {
                $result = BehatLogUtil::analyze();
            }
            catch(Exception $e)
            {
                $this->project->log(str_repeat("-", 50), Project::MSG_ERR);
                $this->project->log(str_pad("Behat build failed", 50, " ", STR_PAD_BOTH), Project::MSG_ERR);
                $this->project->log(str_repeat("-", 50), Project::MSG_ERR);
                throw new BuildException($e, $this->location);
            }

            $this->project->log(str_repeat("-", 50));
            $this->project->log(str_pad("Behat build succeeded in $time seconds", 50, " ", STR_PAD_BOTH));
            $this->project->log(str_pad($result["features"]." feature(s) passed", 50, " ", STR_PAD_BOTH));
            $this->project->log(str_pad($result["scenarios"]." scenario(s) passed", 50, " ", STR_PAD_BOTH));
            $this->project->log(str_repeat("-", 50));
        }

        private function addLogging()
        {
            $args = array();

            // create temporary directory for jUnit XML file output from Behat
            if(!is_writable(sys_get_temp_dir()))
                $this->project->log("Temporary directory not writeable", Project::MSG_ERR);

            $path = sys_get_temp_dir().DIRECTORY_SEPARATOR."behat-junit";

            if(!realpath($path))
            {
                if(!mkdir($path, 0777, true) || !is_writable(realpath($path)))
                    $this->project->log("Temporary directory not writeable", Project::MSG_ERR);
            }
            else if(!is_writable(realpath($path)))
                $this->project->log("Temporary directory not writeable", Project::MSG_ERR);

            $this->project->log("Writing Behat jUnit files to $path");

            if(!empty($this->htmlOutput))
            {
                // add junit format output to internal arguments
                $args[] = "-f junit,html --out=$path,".$this->escapePath($this->htmlOutput);
            }
            else
            {
                // add junit format output to internal arguments
                $args[] = "-f junit --out=$path";
            }

            BehatLogUtil::setLogsPath($path);

            return $args;
        }

        /**
         * @return BehatArguments
         */
        public function createArguments()
        {
            return ($this->arguments = new BehatArguments());
        }

        /**
         * Calls behat using the ExecTask with given parameters
         *
         * @param $params
         */
        private function callBehat($params)
        {
            $features = $this->parseFeatures();

            if(empty($this->executable))
                $this->project->log("Please specify the [executable] property", Project::MSG_ERR);

            $exec = new ExecTask();
            $exec->project = $this->project;
            $exec->init();

            $cmd = $this->escapePath($this->executable)." $params ".$features;
            $exec->setCommand($cmd);
            $exec->setDir(new PhingFile(dirname($this->executable)));
            $exec->setEscape(true);
            $exec->setOutputProperty("behat.output");

            $this->project->log("Calling Behat: \n$cmd\n", Project::MSG_INFO);

            $exec->main();
        }

        public function setFeatures($features)
        {
            $features = trim($features);

            if($features == "*" || empty($features))
                $features = null;

            $this->features = $features;
        }

        public function getFeatures()
        {
            return $this->features;
        }

        public function setFeaturesDir($basedir)
        {
            $this->featuresDir = realpath($basedir);
        }

        public function getFeaturesDir()
        {
            return $this->featuresDir;
        }

        private function parseFeatures()
        {
            $featuresArr = array();
            if(!empty($this->features))
                $featuresArr = explode(",", $this->features);

            $features = array();
            foreach($featuresArr as $feature)
            {
                $feature = trim($feature);
                if(!stripos($feature, ".feature"))
                    $feature .= ".feature";

                if(!realpath($this->featuresDir.DIRECTORY_SEPARATOR.$feature))
                    $this->project->log("[$feature] does not exist within ".$this->featuresDir, Project::MSG_WARN);

                $features[] = $this->escapePath($this->featuresDir.DIRECTORY_SEPARATOR.$feature);
            }

            return implode(" ", $features);
        }

        public function setExecutable($executable)
        {
            // check to see if the user has pointed to a valid path
            $executable = realpath($executable);
            if(empty($executable))
                $this->executable = null;

            // if the user pointed directly to the executable, allow it
            else if(is_file($executable))
                $this->executable = $executable;

            // if the path is a directory, try guess the executable path
            else if(is_dir($executable))
            {
                if(realpath($executable.DIRECTORY_SEPARATOR."behat"))
                    $this->executable = $executable.DIRECTORY_SEPARATOR."behat";
            }
            else
                $this->executable = $executable;
        }

        public function getExecutable()
        {
            return $this->executable;
        }

        /**
         * @param boolean $strictMode
         */
        public function setStrict($strictMode)
        {
            $this->strict = $strictMode;
        }

        /**
         * @return boolean
         */
        public function getStrict()
        {
            return $this->strict;
        }

        /**
         * @param string $config
         */
        public function setConfig($config)
        {
            $this->config = $config;
        }

        /**
         * @return string
         */
        public function getConfig()
        {
            return $this->config;
        }

        /**
         * @param string $htmlOutput
         */
        public function setHtmlOutput($htmlOutput)
        {
            $this->htmlOutput = $htmlOutput;
        }

        /**
         * @return string
         */
        public function getHtmlOutput()
        {
            return $this->htmlOutput;
        }

        private function escapePath($path)
        {
            return str_replace(" ", "\ ", $path);
        }
    }