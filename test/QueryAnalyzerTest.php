<?php

include_once 'src/QueryAnalyzer.php';

class QueryAnalyzerTest extends PHPUnit_Framework_TestCase {
	
	public function test_types(
	) {
		$insert = new QueryAnalyzer("INSERT INTO tabla(campo) VALUES ('valor')");
		$select = new QueryAnalyzer("SELECT * FROM tabla");
		$update = new QueryAnalyzer("UPDATE tabla SET campo='valor'");
		$delete = new QueryAnalyzer("DELETE FROM tabla");
		$this->assertEquals("insert", $insert->type());
		$this->assertEquals("select", $select->type());
		$this->assertEquals("update", $update->type());
		$this->assertEquals("delete", $delete->type());
	}

	public function test_types_case(
	) {
		$q1 = new QueryAnalyzer("insert INTO tabla(campo) VALUES ('valor')");
		$q2 = new QueryAnalyzer("Insert INTO tabla(campo) VALUES ('valor')");
		$q3 = new QueryAnalyzer("InserT INTO tabla(campo) VALUES ('valor')");
		$this->assertEquals("insert", $q1->type());
		$this->assertEquals("insert", $q2->type());
		$this->assertEquals("insert", $q3->type());
		$q1 = new QueryAnalyzer("select * FROM tabla");
		$q2 = new QueryAnalyzer("Select * FROM tabla");
		$q3 = new QueryAnalyzer("SelecT * FROM tabla");
		$this->assertEquals("select", $q1->type());
		$this->assertEquals("select", $q2->type());
		$this->assertEquals("select", $q3->type());
		$q1= new QueryAnalyzer("update tabla SET campo='valor'");
		$q2= new QueryAnalyzer("Update tabla SET campo='valor'");
		$q3= new QueryAnalyzer("UpdatE tabla SET campo='valor'");
		$this->assertEquals("update", $q1->type());
		$this->assertEquals("update", $q2->type());
		$this->assertEquals("update", $q3->type());
		$q1 = new QueryAnalyzer("delete FROM tabla");
		$q2 = new QueryAnalyzer("Delete FROM tabla");
		$q3 = new QueryAnalyzer("DeletE FROM tabla");
		$this->assertEquals("delete", $q1->type());
		$this->assertEquals("delete", $q2->type());
		$this->assertEquals("delete", $q3->type());
	}

	public function test_table(
	) {
		$insert = new QueryAnalyzer("INSERT INTO tabla(campo) VALUES ('valor')");
		$select = new QueryAnalyzer("SELECT * FROM tabla");
		$update = new QueryAnalyzer("UPDATE tabla SET campo='valor'");
		$delete = new QueryAnalyzer("DELETE FROM tabla");
		$this->assertEquals("tabla", $insert->table());
		$this->assertEquals("tabla", $select->table());
		$this->assertEquals("tabla", $update->table());
		$this->assertEquals("tabla", $delete->table());
	}

	public function test_selected_fields(
	) {
		$query = new QueryAnalyzer("INSERT INTO tabla(campo1, campo2) VALUES ('valor1', 'valor2')");
		$this->assertEquals(
			array("campo1", "campo2"), 
			$query->selected_fields()
		);
		$query = new QueryAnalyzer("SELECT campo1, campo2 FROM tabla");
		$this->assertEquals(
			array("campo1", "campo2"), 
			$query->selected_fields()
		);
	}

	public function test_values(
	) {
		$query = new QueryAnalyzer("INSERT INTO tabla(campo1, campo2) VALUES ('valor1', 'valor2')");
		$this->assertEquals(
			array("'valor1'", "'valor2'"), 
			$query->values()
		);
	}

	// TODO -- split should probably be moved to other class
	public function test_split(
	) {
		$query = "INSERT INTO tabla(campo1, campo2) VALUES ('valor1', 'valor2')";
		$analyzer = new QueryAnalyzer($query);
		$this->assertEquals(
			array(
				"INSERT", "INTO", "tabla", "(", "campo1", ",", "campo2",
				")", "VALUES", "(", "'valor1'", ",", "'valor2'", ")"
			),
			$analyzer->split($query)
		);
	}

	public function test_where(
	) {
		$select = new QueryAnalyzer("SELECT * FROM tabla");
		$this->assertEquals(
			array(true),
			$select->where_condition()
		);
		$select = new QueryAnalyzer("SELECT * FROM tabla WHERE campo='valor'");
		$this->assertEquals(
			array("=", "campo", "'valor'"),
			$select->where_condition()
		);
		$select = new QueryAnalyzer("SELECT * FROM tabla WHERE campo='valor' AND campo2='valor'");
		$this->assertEquals(
			array(
				"AND", 
				array("=", "campo", "'valor'"), 
				array("=", "campo2", "'valor'")
			),
			$select->where_condition()
		);
		$select = new QueryAnalyzer("SELECT * FROM tabla WHERE (campo='valor' AND campo2='valor')");
		$this->assertEquals(
			array(
				"AND", 
				array("=", "campo", "'valor'"), 
				array("=", "campo2", "'valor'")
			),
			$select->where_condition()
		);
		$select = new QueryAnalyzer("SELECT * FROM tabla WHERE (campo='valor' AND campo2='valor') OR campo3='valor'");
		$this->assertEquals(
			array(
				"OR", 
				array(
					"AND", 
					array("=", "campo", "'valor'"),
					array("=", "campo2", "'valor'")
				), 
				array("=", "campo3", "'valor'")
			),
			$select->where_condition()
		);
	}

	public function test_setters(
	) {
		$analyzer = new QueryAnalyzer("UPDATE tabla SET campo='valor', campo2=23");
		$this->assertEquals(
			array(
				"campo" => "'valor'",
				"campo2" => 23
			),
			$analyzer->setters()
		);
	}

	public function test_join(
	) {
		$query = new QueryAnalyzer("SELECT * FROM tabla1, tabla2");
		$this->assertEquals(
			array("tabla1", "tabla2"),
			$query->table()
		);
	}

}

?>
