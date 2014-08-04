<?php 

namespace Pimcore\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Package\PackageInterface;
use Composer\Installer\InstallerInterface;
use Composer\Util\Filesystem;

class Installer implements InstallerInterface
{
	private $composer; 
    private $downloadManager;
    private $filesystem;

	 public function __construct(IOInterface $io, Composer $composer, $type = 'library', Filesystem $filesystem = null)
    {
        $this->composer = $composer;
        $this->downloadManager = $composer->getDownloadManager();
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    /**
     * {@inheritDoc}
     */
    public function getInstallPath(PackageInterface $package)
    {
	
		$docRootName = "./www"; 
		if($configDocRoot = $this->composer->getConfig()->get("document-root-path")) {
			$docRootName = rtrim($configDocRoot,"/");
		}
	
        return $docRootName . '/';
    }

	/**
     * {@inheritDoc}
     */
	public function install(InstalledRepositoryInterface $repo, PackageInterface $package) {
		$installPath = $this->getInstallPath($package);
		
		if (!is_dir($installPath)) {
            mkdir($installPath, 0777, true);
        }
        $this->downloadManager->download($package, $installPath);
		
		// cleanup
		@unlink($installPath . "build.xml");
		@unlink($installPath . "composer.json");
		@unlink($installPath . "phpdox.xml.dist");
		@unlink($installPath . "phpunit.xml.dist");
		@unlink($installPath . "phpunit-no-coverage.xml.dist");
		
		rename($installPath . "plugins_example", $installPath . "plugins");
		rename($installPath . "website_example", $installPath . "website");
		
		$this->recursiveDelete($installPath . "update");
		$this->recursiveDelete($installPath . "build");
		$this->recursiveDelete($installPath . "tests");
		$this->recursiveDelete($installPath . ".svn");
	}
	
	/**
     * {@inheritDoc}
     */
    public function isInstalled(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        return false;
    }
	
	/**
     * {@inheritDoc}
     */
    public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
    {
        throw new \InvalidArgumentException("not supported");
    }

    /**
     * {@inheritDoc}
     */
    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        throw new \InvalidArgumentException("not supported");
    }
	
    /**
     * {@inheritDoc}
     */
    public function supports($packageType)
    {
        return 'pimcore-core' === $packageType;
    }
	
	
	protected function recursiveDelete ($directory, $empty = true) { 

		if(substr($directory,-1) == "/") { 
			$directory = substr($directory,0,-1); 
		} 

		if(!file_exists($directory) || !is_dir($directory)) { 
			return false; 
		} elseif(!is_readable($directory)) { 
			return false; 
		} else { 
			$directoryHandle = opendir($directory);
			$contents = ".";

			while ($contents) {
				$contents = readdir($directoryHandle);
				if(strlen($contents) && $contents != '.' && $contents != '..') {
					$path = $directory . "/" . $contents; 
					
					if(is_dir($path)) { 
						$this->recursiveDelete($path); 
					} else { 
						unlink($path); 
					} 
				} 
			} 
			
			closedir($directoryHandle); 

			if($empty == true) { 
				if(!rmdir($directory)) {
					return false; 
				} 
			} 
			
			return true; 
		} 
	}
}
