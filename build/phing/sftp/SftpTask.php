<?php

require_once 'Net/SFTP.php';

/**
 * Copy files to and from a remote host using sftp. Based on phing scp task.
 *
 * Using phpseclib: http://phpseclib.sourceforge.net/
 *
 * @author Anton Mansurov <anton.mansurov@gmail.com>
 *
 */

class SftpTask extends Task
{
	protected $file = "";
	protected $filesets = array(); // all fileset objects assigned to this task
	protected $todir = "";
	protected $mode = null;

	protected $host = "";
	protected $port = 22;
	protected $username = "";
	protected $password = "";
	protected $autocreate = true;
	protected $fetch = false;

	protected $connection = null;

	protected $count = 0;

	/**
	 * Sets the remote host
	 */
	public function setHost($h)
	{
		$this->host = $h;
	}

	/**
	 * Returns the remote host
	 */
	public function getHost()
	{
		return $this->host;
	}

	/**
	 * Sets the remote host port
	 */
	public function setPort($p)
	{
		$this->port = $p;
	}

	/**
	 * Returns the remote host port
	 */
	public function getPort()
	{
		return $this->port;
	}

	/**
	 * Sets the mode value
	 */
	public function setMode($value)
	{
		$this->mode = $value;
	}

	/**
	 * Returns the mode value
	 */
	public function getMode()
	{
		return $this->mode;
	}

	/**
	 * Sets the username of the user to sftp
	 */
	public function setUsername($username)
	{
		$this->username = $username;
	}

	/**
	 * Returns the username
	 */
	public function getUsername()
	{
		return $this->username;
	}

	/**
	 * Sets the password of the user to sftp
	 */
	public function setPassword($password)
	{
		$this->password = $password;
	}

	/**
	 * Returns the password
	 */
	public function getPassword()
	{
		return $this->password;
	}

	/**
	 * Sets whether to autocreate remote directories
	 */
	public function setAutocreate($autocreate)
	{
		$this->autocreate = (bool) $autocreate;
	}

	/**
	 * Returns whether to autocreate remote directories
	 */
	public function getAutocreate()
	{
		return $this->autocreate;
	}

	/**
	 * Set destination directory
	 */
	public function setTodir($todir)
	{
		$this->todir = $todir;
	}

	/**
	 * Returns the destination directory
	 */
	public function getTodir()
	{
		return $this->todir;
	}

	/**
	 * Sets local filename
	 */
	public function setFile($file)
	{
		$this->file = $file;
	}

	/**
	 * Returns local filename
	 */
	public function getFile()
	{
		return $this->file;
	}

	/**
	 * Sets whether to send (default) or fetch files
	 */
	public function setFetch($fetch)
	{
		$this->fetch = (bool) $fetch;
	}

	/**
	 * Returns whether to send (default) or fetch files
	 */
	public function getFetch()
	{
		return $this->fetch;
	}

	/**
	 * Nested creator, creates a FileSet for this task
	 *
	 * @return FileSet The created fileset object
	 */
	public function createFileSet()
	{
		$num = array_push($this->filesets, new FileSet());
		return $this->filesets[$num - 1];
	}

	public function main()
	{
		if ($this->file == "" && empty($this->filesets))
		{
			throw new BuildException("Missing either a nested fileset or attribute 'file'");
		}

		if ($this->host == "" || $this->username == "")
			throw new BuildException("Attribute 'hostname' and 'username' must be set");

		$this->connection = @new Net_SFTP($this->host, $this->port);
		if (is_null($this->connection))
			throw new BuildException("Could not establish connection to {$this->host}:{$this->port}!");

		$ret = $this->connection->login($this->username, $this->password);
		if (!$ret)
			throw new BuildException("Could not login to {$this->username}@{$this->host}");

		if ($this->file != "")
		{
			$this->copyFile($this->file, basename($this->file));
		}
		else
		{
			if ($this->fetch)
				throw new BuildException('Unable to use filesets to retrieve files from remote server');

			foreach ($this->filesets as $fs)
			{
				$ds = $fs->getDirectoryScanner($this->project);
				$files = $ds->getIncludedFiles();
				$dir = $fs->getDir($this->project)->getPath();
				foreach ($files as $file)
				{
					$path = $dir . DIRECTORY_SEPARATOR . $file;
					$this->copyFile($path, $file);
				}
			}
		}

		$this->log("Copied {$this->counter} file(s) " . ($this->fetch ? "from" : "to") . " '{$this->host}'");
	}

	protected function copyFile($local, $remote)
	{
		$path = rtrim($this->todir, "/") . "/";

		if ($this->fetch)
		{
			$localEndpoint = $path . $remote;
			$remoteEndpoint = $local;

			$ret = $this->connection->get($remoteEndpoint, $localEndpoint);

			if ($ret === false)
				throw new BuildException("Could not fetch remote file '{$remoteEndpoint}'");
		}
		else
		{
			$localEndpoint = $local;
			$remoteEndpoint = $path . $remote;

			if ($this->autocreate)
			{
				$this->connection->mkdir(dirname($remoteEndpoint));
				$this->connection->chmod((empty($this->mode) ? 0755 : $this->mode), dirname($remoteEndpoint));
			}

			$data = @file_get_contents($localEndpoint);

			if ($data === false)
				throw new BuildException("Could not send data from file: '{$localEndpoint}'");

			$ret = @$this->connection->put($remoteEndpoint, $localEndpoint, NET_SFTP_LOCAL_FILE);
			if (!is_null($this->mode))
				$this->connection->chmod($this->mode, $remoteEndpoint);

			if (!$ret)
				throw new BuildException("Could not create remote file '{$remoteEndpoint}'");
		}

		$this->counter++;
	}
}

?>
