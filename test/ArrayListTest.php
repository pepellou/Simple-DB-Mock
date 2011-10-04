<?php

include_once 'src/ArrayList.php';

class ArrayListTest extends PHPUnit_Framework_TestCase {
	
	protected function setUp(
	) {
	}

	protected function tearDown(
	) {
	}

	public function test_removeLast(
	) {
		$values = new ArrayList(array(1, 2, 3, 4, 5));
		$this->assertEquals(5, $values->removeLast());
		$this->assertEquals(4, $values->removeLast());
		$this->assertEquals(3, $values->removeLast());
		$this->assertEquals(2, $values->removeLast());
		$this->assertEquals(1, $values->removeLast());
	}

}

?>
