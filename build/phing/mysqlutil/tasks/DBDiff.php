<?php
    class DBDiff extends Task
    {
        /**
         * @var DSN      Server 1 DSN
         */
        private $server1 = null;

        /**
         * @var DSN      Server 2 DSN
         */
        private $server2 = null;

        private $format = "schema|username:password@host:port";

        private $tables1 = null;
        private $tables2 = null;

        public function main()
        {
            if(empty($this->server1) || empty($this->server2))
                throw new Exception("server1 and server2 both need to be set");

            $this->parseTables();
        }

        private function getTables(DSN $server)
        {
            if(empty($server))
                return null;

            $m = mysql_connect($server->host . ":" . $server->port, $server->username, $server->password);
            if(!$m)
                throw new Exception(mysql_error());

            $q = mysql_query("SHOW TABLES FROM " . $server->schema, $m);
            if(!$q)
                throw new Exception(mysql_error($m));

            $tables = array();
            while($table = mysql_fetch_row($q))
                $tables[] = trim($table[0]);

            mysql_close($m);
            return $tables;
        }

        /**
         * Parse a DSN
         *
         * @param $dsn  DSN (user:password@host:port)
         * @return DSN
         */
        private function parseDSN($dsn)
        {
            if(empty($dsn))
                throw new Exception("Empty DSN");

            $pieces = explode("|", $dsn);
            if(empty($pieces) || count($pieces) < 2)
                throw new Exception("Invalid DSN. Correct format is " . $this->format);

            $schema = "`" . trim($pieces[0]) . "`";

            // split by @, first piece is credentials, second is host details
            $pieces = explode("@", trim($pieces[1]));
            if(empty($pieces) || count($pieces) < 2)
                throw new Exception("Invalid DSN. Correct format is " . $this->format);

            $credentials = $pieces[0];
            $host = $pieces[1];

            $dsn = new DSN();

            // split credentials by :
            $credentials = explode(":", $credentials);
            if(empty($credentials) || count($credentials) < 2)
                throw new Exception("Invalid DSN. Correct format is " . $this->format);

            // split host by :
            $host = explode(":", $host);
            if(empty($host) || count($host) < 1)
                throw new Exception("Invalid DSN. Correct format is " . $this->format);

            $dsn->schema = $schema;
            $dsn->username = trim($credentials[0]);
            $dsn->password = trim($credentials[1]);
            $dsn->host = trim($host[0]);
            if(isset($host[1]))
                $dsn->port = (int) trim($host[1]);

            return $dsn;
        }

        /**
         * @param string $server1
         */
        public function setServer1($server1)
        {
            $this->server1 = $this->parseDSN($server1);
        }

        /**
         * @return string
         */
        public function getServer1()
        {
            return $this->server1;
        }

        /**
         * @param string $server2
         */
        public function setServer2($server2)
        {
            $this->server2 = $this->parseDSN($server2);
        }

        /**
         * @return string
         */
        public function getServer2()
        {
            return $this->server2;
        }

        private function parseTables()
        {
            $this->tables1 = $this->getTables($this->server1);
            $this->tables2 = $this->getTables($this->server2);

            if(empty($this->tables1))
                throw new Exception("Could not find any tables for " . $this->server1->schema . " on " . $this->server1->host);

            if(empty($this->tables2))
                throw new Exception("Could not find any tables for " . $this->server2->schema . " on " . $this->server2->host);
        }
    }

    class DSN
    {
        public $schema;
        public $username;
        public $password;
        public $host;
        public $port = 3306;
    }