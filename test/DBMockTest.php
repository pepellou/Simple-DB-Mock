<?php

include_once 'src/DBMock.php';

class DBMockTest extends PHPUnit_Framework_TestCase {
	
	private $mock;

	protected function setUp(
	) {
		$this->mock = new DBMock();
	}

	protected function tearDown(
	) {
	}

	public function test_insert(
	) {
		$this->mock->query("INSERT INTO tabla(campo1, campo2) VALUES ('valor1', 'valor2')");
		$this->assertEquals(
			array(
				array("id" => 1, "campo1" => "'valor1'", "campo2" =>"'valor2'")
			),
			$this->mock->query("SELECT * FROM tabla")
		);
	}

	public function test_double_insert(
	) {
		$this->mock->query("INSERT INTO tabla(campo1, campo2) VALUES ('valor1', 'valor2')");
		$this->mock->query("INSERT INTO tabla(campo1, campo2) VALUES ('valor1', 'valor2')");
		$this->assertEquals(
			array(
				array("id" => 1, "campo1" => "'valor1'", "campo2" =>"'valor2'"),
				array("id" => 2, "campo1" => "'valor1'", "campo2" =>"'valor2'")
			),
			$this->mock->query("SELECT * FROM tabla")
		);
	}

	public function test_count(
	) {
		$this->assertEquals(0, $this->mock->query("SELECT COUNT(*) FROM tabla"));
		$this->mock->query("INSERT INTO tabla(campo1, campo2) VALUES ('valor1', 'valor2')");
		$this->assertEquals(1, $this->mock->query("SELECT COUNT(*) FROM tabla"));
		$this->mock->query("INSERT INTO tabla(campo1, campo2) VALUES ('valor1', 'valor2')");
		$this->assertEquals(2, $this->mock->query("SELECT COUNT(*) FROM tabla"));
	}

	public function test_select(
	) {
		$this->mock->query("INSERT INTO tabla(campo1, campo2) VALUES ('valor1', 'valor2')");
		$this->mock->query("INSERT INTO tabla(campo1, campo2) VALUES ('valor3', 'valor4')");
		$this->assertEquals(
			array(
				array("id" => 1, "campo1" => "'valor1'", "campo2" =>"'valor2'")
			),
			$this->mock->query("SELECT * FROM tabla WHERE campo1='valor1'")
		);
		$this->assertEquals(
			array(
				array("id" => 1, "campo1" => "'valor1'", "campo2" =>"'valor2'")
			),
			$this->mock->query("SELECT * FROM tabla WHERE id=1")
		);
	}

}

?>
