<?php

include_once 'src/QueryAnalyzer.php';

class DBMock {
	
	var $data;

	public function __construct(
	) {
		$this->data = array();
	}

	public function query(
		$query
	) {
		$analysis = $this->analyze($query);
		$table = $analysis->table();
		if (!isset($this->data[$table])) {
			$this->data[$table] = array();
		}
		switch ($analysis->type()) {
			case "insert":
				$this->data[$table][]= $this->newRow($analysis);
				return true;
			case "select":
				$data = $this->data[$table];
				$select = $analysis->selected_fields();
				if ($select == array('*'))
					return $data;
				else if ($select == array('COUNT', '(', '*', ')'))
					return count($data);
				else {
					print_r($select);
				}
		}
		return null;
	}

	private function analyze(
		$query
	) {
		return new QueryAnalyzer($query);
	}

	private function newRow(
		$analyzer
	) {
		$fields = $analyzer->selected_fields();
		$values = $analyzer->values();
		$row = array();
		for ($pos = 0; $pos < count($fields); $pos++) {
			$row[$fields[$pos]] = $values[$pos];
		}
		return $row;
	}

}

?>
