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
		$this->mock->query("INSERT INTO tabla(campo1, campo2) VALUES ('valor3', 'valor2')");
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
			$this->mock->query("SELECT * FROM tabla WHERE campo1='valor1' AND campo2='valor2'")
		);
		$this->assertEquals(
			array(
				array("id" => 1, "campo1" => "valor1", "campo2" =>"valor2"),
				array("id" => 2, "campo1" => "valor3", "campo2" =>"valor2")
			),
			$this->mock->query("SELECT * FROM tabla WHERE campo2='valor2'")
		);
		$this->assertEquals(
			array(
				array("id" => 1, "campo1" => "valor1", "campo2" =>"valor2")
			),
			$this->mock->query("SELECT * FROM tabla WHERE id=1")
		);
		$this->assertEquals(
			array(),
			$this->mock->query("SELECT * FROM tabla WHERE campo1='valor1' AND campo2='valor3'")
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

	public function test_join(
	) {
		$this->mock->query("INSERT INTO tabla1(campo1, campo2, campo3) VALUES ('v1', 'v11', 2)");
		$this->mock->query("INSERT INTO tabla1(campo1, campo2, campo3) VALUES ('v2', 'v22', 5)");
		$this->mock->query("INSERT INTO tabla2(campo3, tabla1_id) VALUES ('v2', 1)");
		$this->mock->query("INSERT INTO tabla2(campo3, tabla1_id) VALUES ('v3', 1)");
		$this->assertEquals(
			array(
				array(
					"tabla1.id" => 1,
					"campo1" => "v1",
					"campo2" => "v11",
					"tabla1.campo3" => "2",
					"tabla2.id" => 1,
					"tabla2.campo3" => "v2",
					"tabla1_id" => 1
				),
				array(
					"tabla1.id" => 1,
					"campo1" => "v1",
					"campo2" => "v11",
					"tabla1.campo3" => "2",
					"tabla2.id" => 2,
					"tabla2.campo3" => "v3",
					"tabla1_id" => 1
				)
			),
			$this->mock->query("SELECT * FROM tabla1, tabla2 WHERE tabla1_id=tabla1.id")
		);
	}

}

?>
