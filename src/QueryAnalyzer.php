<?php

include_once('src/ArrayList.php');

class QueryAnalyzer {
	
	var $type;
	var $table;
	var $selected_fields;
	var $values;
	var $setters;
	var $order;

	public function __construct(
		$query
	) {
		$words = $this->split($query);
		$this->type = $this->getType($words);
		$this->table = $this->getTableName($this->type, $words);
		$this->selected_fields = $this->getSelectedFields($this->type, $words);
		$this->values = $this->getValues($this->type, $words);
		$this->where_condition = $this->getWhere($words);
		$this->setters = $this->getSetters($words);
		$this->order = $this->getOrder($words);
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

	private function operators(
	) {
		return array("OR", "AND", "=");
	}

	private function isSeparator(
		$word
	) {
		return in_array($word, array(
			" ", ",", "(", ")", "="
		));
	}

	public function split(
		$query
	) {
		$words = array();
		$in_word = false;
		$in_string = false;
		$str_open = "'";
		$prev = 0;
		for ($current = 0; $current < strlen($query); $current++) {
			$c = substr($query, $current, 1);
			if (!$in_string && $this->isSeparator($c)) {
				if ($in_word) {
					$words []= substr($query, $prev, $current - $prev);
				}
				$in_word = false;
				$prev = $current + 1;
				if ($c != " ")
					$words []= $c;
			} else {
				$in_word = true;
				if (in_array($c, array("'", "\""))) {
					if ($in_string) {
						if (($c == $str_open) && (substr($query, $current - 1, 1) != "\\"))
							$in_string = false;
					} else {
						$in_string = true;
						$str_open = $c;
					}
				}
			}
		}
		if ($in_word)
			$words []= substr($query, $prev);
		return $this->slashes($words);
	}

	private function slashes(
		$words
	) {
		$slashed = array();
		foreach ($words as $word) {
			$slashed []= $this->slash($word);
		}
		return $slashed;
	}

	private function slash(
		$word
	) {
		// TODO: this is really slow with big BLOBs (an issue?)
		if (in_array(strtoupper($word), array(
			"=", ",", "'", "(", ")", "UPDATE", "INSERT",
			"SELECT", "VALUES", "INTO", "FROM", "WHERE",
			"DELETE", "ORDER", "BY", "\""
		)))
			return $word;
		$result = "";
		$scaped = false;
		for ($pos = 0; $pos < strlen($word); $pos++) {
			$c = substr($word, $pos, 1);
			if ($c == "\\" && !$scaped) {
				$scaped = true;
			} else {
				if ($scaped) {
					if (in_array(ord($c), array(34, 39, 92)))
						$result = $result.$c;
					else
						$result = $result.chr($c);
				} else {
					$result = $result.$c;
				}
				$scaped = false;
			}
		}
		return $result;
	}

	private function getTableNames(
		$words
	) {
		$pos = $this->nextInWords("FROM", $words, 1) + 1;
		$names = array($words[$pos++]);
		while ($pos < count($words) && $words[$pos++] == ',') {
			$names []= $words[$pos++];
		}		
		return count($names) == 1
			? $names[0]
			: $names;
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
				return $this->getTableNames($words);
		}
	}

	private function getOrder(
		$words
	) {
		$pos = $this->nextInWords("ORDER", $words, 1); 
		$order = array();
		if ($pos !== null) {
			$pos++;
			if ($words[$pos] == "BY") {
				$pos++;
				while ($pos < count($words)) {
					$field = $words[$pos++];
					$ord = "ASC";
					if (($pos < count($words)) && ($words[$pos] != ",")) {
						$ord = $words[$pos++];
					}
					$pos++;
					$order[] = array($field, $ord);
				}
			}
		}
		return $order;
	}

	private function getSetters(
		$words
	) {
		$pos = $this->nextInWords("SET", $words, 1); 
		$setters = array();
		if ($pos !== null) {
			$pos++;
			while ($pos < count($words)) {
				$field = $words[$pos++];
				$pos++;
				$value = $words[$pos++];
				$pos++;
				$setters[$field] = $value;
			}
		}
		return $setters;
	}

	private function getWhere(
		$words
	) {
		$pos = $this->nextInWords("WHERE", $words, 1); 
		if ($pos === null)
			return array('true');
		$postfija = array();
		$pos++;
		$stack = array();
		while (($pos < count($words)) && ($words[$pos] != "ORDER")) {
			$word = $words[$pos];
			if (in_array($word, $this->operators())) {
				$end = false;
				while (count($stack) > 0 && ($stack[count($stack) - 1] != "(") && !$end) {
					$top = $stack[count($stack) - 1];
					if (in_array($top, $this->operators())) {
						if ($this->priority($top) >= $this->priority($word)) {
							$last = $stack[count($stack) - 1];
							$postfija []= $last;
							$stack = $this->removeLast($stack);
						} else {
							$end = true;
						}
					} else {
						$last = $stack[count($stack) - 1];
						$postfija []= $last;
						$stack = $this->removeLast($stack);
					}
				}
				$stack []= $word;
			} else if ($word == '(') {
				$stack []= $word;
			} else if ($word == ')') {
				while (count($stack) > 0 && ($stack[count($stack) - 1] != "(")) {
					$last = $stack[count($stack) - 1];
					$postfija []= $last;
					$stack = $this->removeLast($stack);
				}
				if ($stack[count($stack) - 1] == "(")
					$stack = $this->removeLast($stack);
			} else {
				$postfija []= $word;
			}
			$pos++;
		}
		while (count($stack) > 0) {
			$last = $stack[count($stack) - 1];
			$postfija []= $last;
			$stack = $this->removeLast($stack);
		}
		$tree = $this->buildTree(new ArrayList($postfija));
		return $tree;
	}

	private function buildTree(
		$post
	) {
		$last = $post->removeLast();
		if (in_array($last, $this->operators())) {
			$right = $this->buildTree($post);
			$left = $this->buildTree($post);
			return array($last, $left, $right);
		} else {
			return $last;
		}		
	}

	private function priority(
		$operator
	) {
		return array_search($operator, $this->operators());
	}

	private function removeLast(
		$stack
	) {
		// TODO extract to class Stack
		return array_slice($stack, 0, -1);
	}

	public function where_condition(
	) {
		return $this->where_condition;
	}

	private function nextInWords(
		$word,
		$words,
		$init = 0
	) {
		$pos = $init;
		while ($pos < count($words) && strtoupper($words[$pos]) != $word)
			$pos++;
		return ($pos == count($words)) ? null : $pos;
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

	public function setters(
	) {
		return $this->setters;
	}

	public function order(
	) {
		return $this->order;
	}

}

?>
