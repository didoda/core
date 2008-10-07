<?php 
/**
 * Areas, sections test cases...
 * 
 * @author giangi@qwerg.com ste@channelweb.it
 * 
 */

require_once ROOT . DS . APP_DIR. DS. 'tests'. DS . 'bedita_base.test.php';

class DocumentTestCase extends BeditaTestCase {

 	var $uses		= array('BEObject','Document', 'SearchText', 'Tree') ;
    var $dataSource	= 'test' ;	
 	var $components	= array('Transaction', 'Permission') ;

 	protected $inserted = array();
 	
    /////////////////////////////////////////////////
    //      TEST METHODS
    /////////////////////////////////////////////////
 	
 	function testInsert() {
		$this->requiredData(array("insert"));
		$result = $this->Document->save($this->data['insert']) ;
		$this->assertEqual($result,true);		
		if(!$result) {
			debug($this->Document->validationErrors);
			return ;
		}
		
		$result = $this->Document->findById($this->Document->id);
		pr("Document created:");
		pr($result);
		$this->inserted[] = $this->Document->id;
	} 
	
 	function testSearch() {

 		$searches = $this->data['searches'];
 		foreach ($searches as $s) {
	 		pr("Search string:".$s);
			$res = $this->BEObject->findObjs(null, null, 
				array(22, "search" => $s));
	 		pr($res);
 		}

 	 	// tree search
 		foreach ($this->data['searchTree'] as $treeId) {
	 		foreach ($searches as $s) {
		 		pr("Tree id: $treeId - search string:".$s);
				$res = $this->Tree->getChildren($treeId, null, null,
					array(22, "search" => $s));
		 		pr($res);
	 		}
 		}
 		
 	}	
	
 	function testDelete() {
        pr("Removinge inserted documents:");
        foreach ($this->inserted as $ins) {
        	$result = $this->Document->delete($ins);
			$this->assertEqual($result, true);		
			pr("Document deleted");
        }        
 	}
 	
    /////////////////////////////////////////////////
	//     END TEST METHODS
	/////////////////////////////////////////////////

	protected function cleanUp() {
		$this->Transaction->rollback() ;
	}
	
	public   function __construct () {
		parent::__construct('Document', dirname(__FILE__)) ;
	}	
}

?> 