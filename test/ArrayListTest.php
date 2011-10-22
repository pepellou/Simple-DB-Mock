<?php

include_once 'src/ArrayList.php';

class ArrayListTest extends PHPUnit_Framework_TestCase {
	
	public function test_removeLast(
	) {
		$values = new ArrayList(array(1, 2, 3));
		$this->assertEquals(3, $values->removeLast());
		$this->assertEquals(2, $values->removeLast());
		$this->assertEquals(1, $values->removeLast());
	}

}

?>
