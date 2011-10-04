<?php

class QueryAnalyzer {
	
	var $type;
	var $table;
	var $selected_fields;
	var $values;

	public function __construct(
		$query
	) {
		$words = $this->split($query);
		$this->type = $this->getType($words);
		$this->table = $this->getTableName($this->type, $words);
		$this->selected_fields = $this->getSelectedFields($this->type, $words);
		$this->values = $this->getValues($this->type, $words);
	}

	public function getValues(
		$type,
		$words
	) {
		switch ($type) {
			case "insert":
				return $this->readListTo(
					$words, 
					$this->nextInWords(
						"(", 
						$words, 
						$this->nextInWords("VALUES", $words, 1)
					) + 1, 
					")"
				);
		}
		return array();
	}

	public function getSelectedFields(
		$type,
		$words
	) {
		switch ($type) {
			case "insert":
				return $this->readListTo(
					$words, 
					$this->nextInWords("(", $words, 1) + 1, 
					")"
				);
			case "select":
				return $this->readListTo($words, 1, "FROM");
		}
		return array();
	}

	public function readListTo(
		$words,
		$from,
		$toWord
	) {
		$list = array();
		$pos = $from;
		while ($words[$pos] != $toWord) {
			$word = $words[$pos];
			if ($word != ",")
				$list []= $word;
			$pos++;
		}
		return $list;
	}

	public function split(
		$query
	) {
		$separators = array(" ", ",", "(", ")");
		$words = array();
		$in_word = false;
		$prev = 0;
		for ($pos = 0; $pos < strlen($query); $pos++) {
			$c = substr($query, $pos, 1);
			if (in_array($c, $separators)) {
				if ($in_word)
					$words []= substr($query, $prev, $pos - $prev);
				$in_word = false;
				$prev = $pos + 1;
				if ($c != " ")
					$words []= $c;
			} else {
				$in_word = true;
			}
		}
		if ($in_word)
			$words []= substr($query, $prev);
		return $words;
	}

	private function getTableName(
		$type,
		$words
	) {
		switch ($type) {
			case "insert":
			case "delete":
				return $words[2];
			case "update":
				return $words[1];
			case "select":
				return $words[$this->nextInWords("FROM", $words, 1) + 1];
		}
	}

	private function nextInWords(
		$word,
		$words,
		$init = 0
	) {
		$pos = $init;
		while (strtoupper($words[$pos]) != $word)
			$pos++;
		return $pos;
	}

	private function getType(
		$words
	) {
		switch (strtoupper($words[0])) {
			case "INSERT":
				return "insert";
			case "SELECT":
				return "select";
			case "UPDATE":
				return "update";
			case "DELETE":
				return "delete";
		}
		return null;
	}

	public function type(
	) {
		return $this->type;
	}

	public function table(
	) {
		return $this->table;
	}

	public function selected_fields(
	) {
		return $this->selected_fields;
	}

	public function values(
	) {
		return $this->values;
	}

}

?>
