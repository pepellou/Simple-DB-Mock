<?php

class ArrayList {

	private $data;

	public function __construct(
		$values
	) {
		$this->data = $values;
	}

	public function removeLast(
	) {
		$last = $this->data[count($this->data) - 1];
		$this->data = array_slice($this->data, 0, -1);
		return $last;
	}

}
