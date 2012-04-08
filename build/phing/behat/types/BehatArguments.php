<?php
    /**
     *
     */
    class BehatArguments
    {
        private $arguments = null;

        public function createArgument()
        {
            return ($this->arguments[] = new BehatArgument());
        }

        public function getArguments()
        {
            $args = array();

            foreach($this->arguments as $arg)
            {
                $args[] = $arg->getCmd();
            }

            return $args;
        }
    }

    class BehatArgument
    {
        private $cmd = null;

        public function setCmd($cmd)
        {
            $this->cmd = trim($cmd);
        }

        public function getCmd()
        {
            return $this->cmd;
        }
    }
