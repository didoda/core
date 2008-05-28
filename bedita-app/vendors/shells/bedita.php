<?php 

App::import('Model', 'DataSource');
App::import('Model', 'Stream');
App::import('Component', 'Transaction');
vendor('splitter_sql');

class DataSourceTest extends DataSource {
	
	function executeQuery($db,$script) {
		$sql = file_get_contents($script);
		$queries = array();
		$SplitterSql = new SplitterSql() ;
		$SplitterSql->parse($queries, $sql) ;
		foreach($queries as $q) {	
			if(strlen($q)>1) {
				$res = $db->execute($q);
				if($res === false) {
					throw new Exception("Error executing query: ".$q."\n");
				}
			}
		}
	}
	
	function executeInsert($db,$script) {
		// split in blocks
		$blocks = $this->createChunks($script);

		// call query to avoid foreign key checks, on data insert
		$res = $db->execute("SET FOREIGN_KEY_CHECKS=0");
		
		// call parse on every block and populate $queries array
		$queries = array();
		$SplitterSql = new SplitterSql() ;
		foreach($blocks as $key => $block) {
			$SplitterSql->parse($queries, $block) ;
			// call queries (except for views creation)
			foreach($queries as $q) {	
				if(strlen($q)>1) {
					if(strpos($q,"CREATE ALGORITHM") === false) {
						//echo "executing query " . $q . "\n";
						$res = $db->execute($q);
						if($res === false) {
							throw new Exception("Error executing query: ".$q."\n");
						}
					}
				}
			}
		}
	}

	function simpleInsert($db, $sqlFileName) {
		$handle = fopen($sqlFileName, "r");
		if($handle === FALSE) 
			throw new Exception("Error opening file: ".$sqlFileName);
		$q = "";
		while(!feof($handle)) {
			$line = fgets($handle);
			if($line === FALSE && !feof($handle)) {
				throw new Exception("Error reading file line");
			}
			if(strncmp($line, "INSERT INTO ", 12) == 0) {
				if(strlen($q) > 0) {
					$res = $db->execute($q);
					if($res === false) {
						throw new Exception("Error executing query: ".$q."\n");
					}
				}
				$q="";
			}
			$q .= $line;
		}
		// last query...
		if(strlen($q) > 0) {
			$res = $db->execute($q);
			if($res === false) {
				throw new Exception("Error executing query: ".$q."\n");
			}
		}
	}
	
	function createChunks($script) {
		$chunks = array();
		$handle = fopen($script, "r");
		$data = "";
		$counter=0;$ccounter=0;
		$endchar = ");\n";
		while (!feof($handle)) {
		   $buffer = fgets($handle, 4096);
		   $data.=$buffer;
		   if($counter>500 && ( substr( $buffer, strlen( $buffer ) - strlen( $endchar ) ) == $endchar ) ) { // check if $counter > 500 and $buffer ends with );
		   		$counter=0;
				$chunks[$ccounter++]=$data;
				$data="";
		   } else {
				$counter++;
		   }
		}
		fclose($handle);
		return $chunks;
	}
}

class DumpModel extends AppModel {
	var $useTable = "objects";
};

class DbDump {
	
	private $model = NULL;
	
	public function __construct() {
		$this->model = new DumpModel();
	}
	
	public function tableList() {
   		$tables = $this->model->execute("show tables");
    	$res = array();
    	foreach ($tables as $k=>$v) {
    		$t1 = array_values($v);
    		$t2 = array_values($t1[0]);
    		if (strncasecmp($t2[0], 'view_', 5) !== 0) // exclude views
    			$res[]=$t2[0] ;
    	}
    	return $res;
    }
    
    public function tableDetails($tables, $handle) {

    	fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n");
    	
    	foreach ($tables as $t) {
    		$this->model->setSource($t); 
    		$select = $this->model->find('all');
			foreach ($select as $sel) {
				$fields = "";
				$values = "";
				$count = 0;
				foreach ($sel['DumpModel'] as $k=>$v) {
					if($count > 0) {
						$fields .= ",";
						$values .= ",";
					}
					$fields .= "`$k`";
					if($v == NULL)
						$values .= "NULL";					
					else 
						$values .= "'".addslashes($v)."'";
					$count++;
				}
				$res = "INSERT INTO $t (".$fields.") VALUES ($values);\n";
    			fwrite($handle, $res);
			}
    	}
    	return $res;
    }
	
}

class BeditaShell extends Shell {

	const DEFAULT_ZIP_FILE 		= 'bedita-export.zip' ;
	
	function updateDb() {
        $dbCfg = 'default';
    	if (isset($this->params['db'])) {
            $dbCfg = $this->params['db'];
    	}
		
		if (!defined('SQL_SCRIPT_PATH')) { // cambiare opportunamente questo path
	        $this->out("SQL_SCRIPT_PATH has to be defined in ".APP_DIR."/config/database.php");
			return;
		}
    	$sqlDataDump = SQL_SCRIPT_PATH . 'bedita_init_data.sql';
    	if (isset($this->params['data'])) {
            if(file_exists(SQL_SCRIPT_PATH . $this->params['data'])) {
    			$sqlDataDump = SQL_SCRIPT_PATH .$this->params['data'];
            } else {
    			$sqlDataDump = $this->params['data'];
            	if(!file_exists($sqlDataDump)) {
	        		$this->out("data file $sqlDataDump not found");
					return;
            	}
            }
    	}
    	
		$db =& ConnectionManager::getDataSource($dbCfg);
    	$hostName = $db->config['host'];
    	$dbName = $db->config['database'];
		$this->out("Updating bedita db config: $dbCfg - [host=".$hostName.", database=".$dbName."]");
        $this->hr();

        $transaction = new TransactionComponent($dbCfg);
		$transaction->begin();
        
        $this->DataSourceTest =& new DataSourceTest();
		$script = SQL_SCRIPT_PATH . "bedita_schema.sql";
		$this->out("Update schema from $script");
		$this->DataSourceTest->executeQuery($db,$script);

		$script = SQL_SCRIPT_PATH . "bedita_procedure.sql";
		$this->out("Create procedures from $script");
        $this->DataSourceTest->executeQuery($db,$script);
        
		if (isset($this->params['nodata'])) {
			$this->out("No data inserted");
		} else {
	        $this->out("Load data from $sqlDataDump");
			$this->DataSourceTest->executeInsert($db, $sqlDataDump);
		}
       	$this->out("$dbCfg database updated");
		$transaction->commit();
		
		if (isset($this->params['media'])) {
            $this->extractMediaZip($this->params['media']);
    	}
       
       $this->out("checking media files");
       $this->checkMedia();
       $this->out("bye");
       
    }

    function import() {
        $dbCfg = 'default';
    	if (isset($this->params['db'])) {
            $dbCfg = $this->params['db'];
    	}
		if (!defined('SQL_SCRIPT_PATH')) { // cambiare opportunamente questo path
	        $this->out("SQL_SCRIPT_PATH has to be defined in ".APP_DIR."/config/database.php");
			return;
		}

		$basePath = $this->setupTempDir();
		
		$zipFile = self::DEFAULT_ZIP_FILE;
    	if (isset($this->params['f'])) {
            $zipFile = $this->params['f'];
    	}
  		$this->out("Importing file $zipFile");
    	$zip = new ZipArchive;
		if ($zip->open($zipFile) === TRUE) {
			$zip->extractTo($basePath);
			$zip->close();
  			$this->out("Export files extracted...");
		} else {
  			$this->out("Error opening zip file $zipFile!!");
		}
		$sqlFileName = $basePath.DS."bedita-data.sql";
		
		$db =& ConnectionManager::getDataSource($dbCfg);
    	$hostName = $db->config['host'];
    	$dbName = $db->config['database'];
		$this->out("Importing data using bedita db config: $dbCfg - [host=".$hostName.", database=".$dbName."]");
        $this->hr();

        $transaction = new TransactionComponent($dbCfg);
		$transaction->begin();
        
        $this->DataSourceTest =& new DataSourceTest();
		$script = SQL_SCRIPT_PATH . "bedita_schema.sql";
		$this->out("Update schema from $script");
		$this->DataSourceTest->executeQuery($db,$script);

		$script = SQL_SCRIPT_PATH . "bedita_procedure.sql";
		$this->out("Create procedures from $script");
        $this->DataSourceTest->executeQuery($db,$script);
        
		$this->out("Load data from $sqlFileName");
        $this->DataSourceTest->simpleInsert($db, $sqlFileName);
		unlink($sqlFileName);
		$this->out("$dbCfg database updated");
		$transaction->commit();
		
		$this->out("bye");
    }

    
    public function export() {
        $expFile = self::DEFAULT_ZIP_FILE;
    	if (isset($this->params['f'])) {
            $expFile = $this->params['f'];
    	}
		if(file_exists($expFile)) {
			$res = $this->in("$expFile exists, overwrite? [y/n]");
			if($res == "y") {
				if(!unlink($expFile)){
					throw new Exception("Error deleting $expFile");
				}
			} else {
				$this->out("Export aborted. Bye.");
				return;
			}
		}

		$dbDump = new DbDump();
		$tables = $dbDump->tableList();
		
		$basePath = $this->setupTempDir();
//		$basePath = getcwd().DS."export-tmp".DS;
		$sqlFileName = $basePath."bedita-data.sql";
		
		$this->out("Creating SQL dump....");
		$handle = fopen($sqlFileName, "w");
		if($handle === FALSE) 
			throw new Exception("Error opening file: ".$sqlFileName);
		$dbDump->tableDetails($tables, $handle);
		fclose($handle);
       	$this->out("Exporting to $expFile");
		$zip = new ZipArchive;
		$res = $zip->open($expFile, ZIPARCHIVE::CREATE);
		if($res === TRUE) {
			if(!$zip->addFile($sqlFileName, "bedita-data.sql"))
				throw new Exception("Error adding SQL file to zip");
		} else {
			throw new Exception("Error opening zip file $expFile - error code $res");
		}
       	$this->out("SQL data exported");
       	$this->out("Exporting media files");
       	
       	$folder=& new Folder(MEDIA_ROOT);
        $tree= $folder->tree(MEDIA_ROOT, false);
        foreach ($tree as $files) {
            foreach ($files as $file) {
                if (!is_dir($file)) {
       				$contents = file_get_contents($file);
        			if ( $contents === false ) {
						throw new Exception("Error reading file content: $file");
        			}
					$p = substr($file, strlen(MEDIA_ROOT));
					if(!$zip->addFromString("media".DS.$p, $contents )) {
						throw new Exception("Error adding $p to zip file");
					}
					unset($contents);
                }
            }
        }
		$zip->close();
       	$this->out("$expFile created");
    }
    
    private function setupTempDir() {
    	$basePath = getcwd().DS."export-tmp".DS;
		if(!is_dir($basePath)) {
			if(!mkdir($basePath))
				throw new Exception("Error creating temp dir: ".$basePath);
		} else {
    		$this->__clean($basePath);
		}
    	return $basePath;
    }
    
    private function extractMediaZip($zipFile) {
		$zip = new ZipArchive;
		if ($zip->open($zipFile) === TRUE) {
			$zip->extractTo(MEDIA_ROOT);
			$zip->close();
  			$this->out("Media files extracted");
		} else {
  			$this->out("Error media file $zipFile not found!!");
		}
    }
    
    function test() {
		pr($this->params);
		pr($this->args);
    }

	public function checkMedia() {

		$stream = new Stream();
        // check filesystem
		$this->out("checkMedia - checking filesystem");
		$folder=& new Folder(MEDIA_ROOT);
        $tree= $folder->tree(MEDIA_ROOT, false);
		$mediaOk = true;
        foreach ($tree as $files) {
            foreach ($files as $file) {
                if (!is_dir($file)) {
                    $file=& new File($file);
					$p = substr($file->pwd(), strlen(MEDIA_ROOT));
					if(stripos($p, "/imgcache/") !== 0) {
						$f = $stream->findByPath($p);
						if($f === false) {
							$this->out("File $p not found on db!!");
							$mediaOk = false;
						}
					}
                }
            }
        }
        if($mediaOk) {
			$this->out("checkMedia - filesystem OK");
        }
        // check db
		$this->out("checkMedia - checking database");
        $allStream = $stream->findAll();
		$mediaOk = true;
        foreach ($allStream as $v) {
        	$p = $v['Stream']['path'];
        	if(!file_exists(MEDIA_ROOT.$p)) {
					$this->out("File $p not found on filesystem!!");
					$mediaOk = false;
        	}
        }
        if($mediaOk) {
			$this->out("checkMedia - database OK");
        }
	}    
    
    private function __clean($path) {
        
        $folder=& new Folder($path);
        $list = $folder->ls();

        foreach ($list[0] as $d) {
        	if($d[0] != '.') { // don't delete hidden dirs (.svn,...)
	        	if(!$folder->delete($folder->path.DS.$d)) {
	                throw new Exception("Error deleting dir $d");
	            }
        	}
        }
        foreach ($list[1] as $f) {
        	$file = new File($folder->path.DS.$f);
        	if(!$file->delete()) {
                throw new Exception("Error deleting file $f");
            }
        }
        return ;
    }    
        
    function checkIni() {
        @include APP. DS . 'config' . DS . 'bedita.ini.php.sample';
        $cfgSample = $config;
        @include APP. DS . 'config' . DS . 'bedita.ini.php';
        $sampleDiff = array_diff_key($cfgSample, $config);
        if(!empty($sampleDiff)) {
        	$this->out("Config to add [not in bedita.ini.php]: \n");
        	foreach ($sampleDiff as $k=>$v) {
                if(is_array($v)) {
                    $this->out("\$confg['$k']=");
                    print_r($v);
                } else {
                    $this->out("\$config['$k']=$v");
                }
        	}
        }
        
        $iniDiff = array_diff_key($config, $cfgSample);
        if(!empty($iniDiff)) {
            $this->out("\nConfig to remove [no more bedita.ini.php.sample]: \n");
            foreach ($iniDiff as $k=>$v) {
                if(is_array($v)) {
                    $this->out("\$confg['$k']=");
                    print_r($v);
                } else {
                    $this->out("\$config['$k']=$v");
                }
            }
        }
        
        if(empty($iniDiff) && empty($sampleDiff)) {
            $this->out("\nNo config key difference.");
        }

        $valDiff = array_diff($config, $cfgSample);
        if(empty($valDiff)) {
            $this->out("\nNo config values difference.");
        } else {
            $this->out("\nConfig values that are different in bedita.ini.php:\n");
            foreach ($valDiff as $k=>$v) {
                if(is_array($v)) {
                    $this->out("\$confg['$k']=");
                    print_r($v);
                } else {
                    $this->out("\$config['$k']=$v");
                }
            }
        }        
    }
    
    function cleanup() {
		$basePath = TMP;
    	if (isset($this->params['frontend'])) {
    		$basePath = $this->params['frontend'].DS."tmp".DS;
            $this->out('Cleaning dir: '.$basePath);
    		
    	}
        if (!isset($this->params['nologs'])) {
    	   $this->__clean($basePath . 'logs');
            $this->out('Logs cleaned.');
        }
        $this->__clean($basePath . 'cache' . DS . 'models');
        $this->__clean($basePath . 'cache' . DS . 'persistent');        
        $this->__clean($basePath . 'cache' . DS . 'views');        
        $this->out('Cache cleaned.');
        $this->__clean($basePath . 'smarty' . DS . 'compile');
        $this->__clean($basePath . 'smarty' . DS . 'cache');
        $this->out('Smarty compiled/cache cleaned.');

        if (isset($this->params['media'])) {
       
           $this->__clean(MEDIA_ROOT . DS. 'imgcache');
           $folder= new Folder(MEDIA_ROOT);
           $dirs = $folder->ls();
           foreach ($dirs[0] as $d) {
           	    if($d !== 'imgcache') {
           	    	$folder->delete(MEDIA_ROOT . DS. $d);
           	    }
           }
           $this->out('Media files cleaned.');
        }
    }    

	function help() {
        $this->out('Available functions:');
        $this->out('1. updateDb: update database with bedita-db sql scripts');
  		$this->out(' ');
        $this->out('    Usage: updateDb [-db <dbname>] [-data <sql>] [-nodata] [-media <zipfile>]');
  		$this->out(' ');
  		$this->out("    -db <dbname>\t use db configuration <dbname> specified in config/database.php");
  		$this->out("    -nodata <sql>   \t don't insert data");
  		$this->out("    -data <sql>     \t use <sql> data dump, use absolute path if not in bedita-db/");
  		$this->out("    -media <zipfile> \t restore media files in <zipfile>");
  		$this->out(' ');
  		$this->out('2. cleanup: cleanup cahe, compile, log files');
        $this->out(' ');
        $this->out('    Usage: cleanup [-frontend <frontend path>] [-nologs] [-media]');
        $this->out(' ');
        $this->out("    -frontend \t clean files in <frontend path> [use frontend /app path]");
        $this->out("    -nologs \t don't clean log files");
        $this->out("    -media  \t clean media files in MEDIA_ROOT (default no)");
        $this->out(' ');
        $this->out('3. checkIni: check difference between bedita.ini.php and .sample');
        $this->out(' ');
        $this->out('4. checkMedia: check media files on db and filesystem');
        $this->out(' ');
        $this->out('5. export: export media files and data dump');
  		$this->out(' ');
        $this->out('    Usage: export -f <zip-filename>');
        $this->out(' ');
  		$this->out("    -f <zip-filename>\t file to export, default ".self::DEFAULT_ZIP_FILE);
        $this->out(' ');
        $this->out('6. import: import media files and data dump');
  		$this->out(' ');
  		$this->out('    Usage: import [-f <zip-filename>] [-db <dbname>]');
        $this->out(' ');
  		$this->out("    -f <zip-filename>\t file to import, default ".self::DEFAULT_ZIP_FILE);
        $this->out("    -db <dbname>\t use db configuration <dbname> specified in config/database.php");
        $this->out(' ');
	}
}

?>