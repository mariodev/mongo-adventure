<?php
/**
 * Author: mario <mario16@poczta.fm>
 *
 * My copy of PHP & MongoDB example application
 */
session_cache_limiter(false);
session_start();

require 'lib/Slim/Slim/Slim.php';

class MyView extends Slim_View {
	public function helper() {
		return 'dummy';
	} 
}

$app = new Slim(array(
	'view' => new MyView()
));

//GET /articles/new
$app->get('/articles/new', function () use ($app) {
    $app->render('blogpost.html', array());
});

//GET /articles
$app->get('/articles', function () use ($app) {
	try {
		$conn = new Mongo();
		$db = $conn->selectDB('myblogsite');
		$coll = $db->selectCollection('articles');
	} catch(MongoConnectionException $e) {
		die("Failed to connect to database " . $e->getMessage());
	}
	$cursor = $coll->find();
	$paginator = new Paginator($cursor, 10);
	$cursor->sort(array('created_at'=>-1))->skip($paginator->get_skip())->limit(10);

    $app->render('blogs.html', array('cursor' => $cursor, 'paginator' => $paginator));
});

//PUT /articles - create new article post
$app->put('/articles', function () use ($app) {
	try {
		$conn = new Mongo();
		$db = $conn->selectDB('myblogsite');
		$coll = $db->selectCollection('articles');

		// some dummy validating
		$tags_clean = array_filter(array_map(function($tag) {
			return trim($tag);
		}, explode(',', $app->request()->post('id_tags'))));

		$article = array(
			'title' => $app->request()->post('id_title'),
			'content' => $app->request()->post('id_content'),
			'created_at' => new MongoDate(),
			'tags' => $tags_clean
		);
		$coll->insert($article);
	} catch(MongoConnectionException $e) {
		die("Failed to connect to database." . $e->getMessage());
	} catch(MongoException $e) {
		die('Failed to insert data ' . $e->getMessage());
	}

	$app->flash('success', 'Article saved successfully. ID: ' . $article['_id']);
	// die('<pre>' . print_r($b, 1) . '</pre>');

	$app->redirect('/blog/articles/new');
});

//POST /articles/:id - update article
$app->post('/articles/:id', function ($id) use ($app) {
	try {
		$conn = new Mongo();
		$coll = $conn->myblogsite->articles;

		// some dummy validating
		$tags_clean = array_filter(array_map(function($tag) {
			return trim($tag);
		}, explode(',', $app->request()->post('id_tags'))));

		$article = array(
			'title' => $app->request()->post('id_title'),
			'content' => $app->request()->post('id_content'),
			'created_at' => new MongoDate(),
			'tags' => $tags_clean
		);
		$coll->update(array('_id' => new MongoId($id)), $article);
	} catch(MongoConnectionException $e) {
		die("Failed to connect to database." . $e->getMessage());
	} catch(MongoException $e) {
		die('Failed to update data ' . $e->getMessage());
	}

	$app->flash('success', 'Article updated successfully. ID: ' . $id);
	// die('<pre>' . print_r($b, 1) . '</pre>');

	$app->redirect('/blog/articles/new');
});

//GET /articles/edit/:id
$app->get('/articles/:id/edit', function ($id) use ($app) {
	// die('<pre>' . print_r($this, 1) . '</pre>');

	try {
		$conn = new Mongo();
		$db = $conn->selectDB('myblogsite');
		$coll = $db->selectCollection('articles');
	} catch(MongoConnectionException $e) {
		die("Failed to connect to database " . $e->getMessage());
	}

	$article = $coll->findOne(array('_id' => new MongoId($id)));
	// die('<pre>' . print_r($article, 1) . '</pre>');

    $app->render('blogpost.html', array('article' => $article));
});

//GET /articles/:id
$app->get('/articles/:id', function ($id) use ($app) {
	try {
		$conn = new Mongo();
		$db = $conn->selectDB('myblogsite');
		$coll = $db->selectCollection('articles');
	} catch(MongoConnectionException $e) {
		die("Failed to connect to database " . $e->getMessage());
	}

	$article = $coll->findOne(array('_id' => new MongoId($id)));

    $app->render('blog.html', array('article' => $article));
});

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

//GET /dashboard
$app->get('/dashboard', function () use ($app) {

	try {
		$conn = new Mongo();
		$db = $conn->selectDB('myblogsite');
		$coll = $db->selectCollection('articles');
	} catch(MongoConnectionException $e) {
		die("Failed to connect to database " . $e->getMessage());
	}

	$cursor = $coll->find(array(), array('title', 'created_at'));
	$paginator = new Paginator($cursor, 5);
	// die('<pre>' . print_r($paginator, 1) . '</pre>');

	$cursor->sort(array('created_at'=>-1))->skip($paginator->get_skip())->limit(5);

    $app->render('dashboard.html', array('cursor' => $cursor, 'paginator' => $paginator));
});

$app->run();
