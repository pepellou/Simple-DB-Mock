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
				array("id" => 1, "campo1" => "valor1", "campo2" =>"valor2")
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
				array("id" => 1, "campo1" => "valor1", "campo2" =>"valor2"),
				array("id" => 2, "campo1" => "valor1", "campo2" =>"valor2")
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
				array("id" => 1, "campo1" => "valor1", "campo2" =>"valor2")
			),
			$this->mock->query("SELECT * FROM tabla WHERE campo1='valor1'")
		);
		$this->assertEquals(
			array(
				array("id" => 1, "campo1" => "valor1", "campo2" =>"valor2")
			),
			$this->mock->query("SELECT * FROM tabla WHERE id=1")
		);
	}

	public function test_max(
	) {
		$this->mock->query("INSERT INTO tabla(campo1, campo2) VALUES ('valor1', 23)");
		$this->mock->query("INSERT INTO tabla(campo1, campo2) VALUES ('valor3', 21)");
		$this->assertEquals(2, $this->mock->query("SELECT MAX(id) FROM tabla"));
		$this->assertEquals(23, $this->mock->query("SELECT MAX(campo2) FROM tabla"));
		$this->assertEquals("valor3", $this->mock->query("SELECT MAX(campo1) FROM tabla"));
	}

	public function test_values_with_quotes(
	) {
		$this->mock->query("INSERT INTO tabla(campo1, campo2) VALUES ('valor1', 23)");
		$this->assertEquals(1, $this->mock->query("SELECT COUNT(*) FROM tabla WHERE campo2=23"));
		$this->assertEquals(1, $this->mock->query("SELECT COUNT(*) FROM tabla WHERE campo2='23'"));
	}

	public function test_set_fields(
	) {
		$this->mock->query("INSERT INTO tabla(campo1, campo2) VALUES ('valor1', 23)");
		$this->assertEquals(
			array(
				array("id" => 1, "campo1" => "valor1", "campo2" => "23")
			),
			$this->mock->query("SELECT * FROM tabla")
		);
		$this->mock->setFields(
			"tabla",
			array("campo3", "campo4")
		);
		$this->assertEquals(
			array(
				array("id" => 1, "campo1" => "valor1", "campo2" => "23", "campo3" => null, "campo4" => null)
			),
			$this->mock->query("SELECT * FROM tabla")
		);
	}

	public function test_update(
	) {
		$this->mock->query("INSERT INTO tabla(campo1, campo2) VALUES ('valor1', 'valor2')");
		$this->mock->query("INSERT INTO tabla(campo1, campo2) VALUES ('valor1', 'valor2')");
		$this->mock->query("UPDATE tabla SET campo1='valor3', campo2='valor4' WHERE id=2");
		$this->assertEquals(
			array(
				array("id" => 1, "campo1" => "valor1", "campo2" =>"valor2"),
				array("id" => 2, "campo1" => "valor3", "campo2" =>"valor4")
			),
			$this->mock->query("SELECT * FROM tabla")
		);
		$this->mock->query("UPDATE tabla SET campo1='VVV', campo2='VVV'");
		$this->assertEquals(
			array(
				array("id" => 1, "campo1" => "VVV", "campo2" =>"VVV"),
				array("id" => 2, "campo1" => "VVV", "campo2" =>"VVV")
			),
			$this->mock->query("SELECT * FROM tabla")
		);
	}

}

?>
