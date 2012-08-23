<?php
class Paginator {
	protected $articles = false;
	protected $limit = 5;
	protected $total = 0;
	protected $current_page = 1;

	function __construct($articles, $limit = 5) {
		$this->limit = $limit;
		$this->total = $articles->count();
		$this->current_page = (isset($_GET['page'])) ? (int) $_GET['page'] : 1;
	}

	public function get_skip() {
		return ($this->current_page - 1) * $this->limit;
	}

	public function get_total_pages() {
		return (int) ceil($this->total / $this->limit);
	}

	public function render() {
		$html = '';
		$prev_disabled = $next_disabled = '';

		if($this->current_page == 1) $prev_disabled = ' class="disabled"';
		if($this->current_page == $this->get_total_pages()) $next_disabled = ' class="disabled"';


		$html .= '<li' . $prev_disabled . '><a href="?page=' . ($this->current_page - 1) . '">Previous</a></li>';
		for ($page=1; $page <= $this->get_total_pages() ; $page++) {
			if($page == $this->current_page) {
				$html .= '<li class="active"><a href="?page=' . $page . '">' . $page . '</a></li>';
			} else {
				$html .= '<li><a href="?page=' . $page . '">' . $page . '</a></li>';
			}
		}
		$html .= '<li' . $next_disabled . '><a href="?page=' . ($this->current_page + 1) . '">Next</a></li>';
		return $html;
	}
}
