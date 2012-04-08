<?php
    /**
     *  @see http://blog.dannykopping.com
     */
    class Sample
    {
        private $name = null;

        /**
         * @param string    $name
         */
        public function setName($name)
        {
            $this->name = $name;
        }

        /**
         * @return string
         */
        public function getName()
        {
            return $this->name;
        }

        /**
         * @return string
         */
        public function sayHello()
        {
            return "Hello, ".$this->getName();
        }
    }
