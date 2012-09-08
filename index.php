<?php
/**
 * Author: mario <mario16@poczta.fm>
 *
 * My copy of PHP & MongoDB example application
 */

require 'lib/Slim/Slim/Slim.php';
require 'lib/TwigView.php';
require 'lib/paginator.php';

require_once('lib/dbconnection.php');
require_once('lib/session.php');
require_once('lib/user.php');
require_once('lib/log.php');
// require_once 'lib/middlewares.php';


$app = new Slim(array(
	'view' => 'TwigView'
));
// $app->hook('slim.before.dispatch', function () use ($app) {
// 	die(var_dump());
// });
$user = new User($app);
// $app->add(new Secret_Middleware());
$start = microtime();

$authorize = function($role = 'all') use($user, $app) {
	return function() use($role, $user, $app) {
		// die(var_dump($role));
		switch($role) {
			case "edit.article":
				$params = $app->router()->getCurrentRoute()->getParams();

				$article = DBConnection::init()
					->getCollection('articles')
					->findOne(array('_id' => new MongoId($params['id']), 'author_id' => new MongoId($user->_id)));
				if(!isset($article)) {
					$app->error();
				}
				break;
			case 'logged':
				if(!$user->isLoggedIn()) {
					$app->error();
				}
				break;
		}
	};
};

$app->view()->appendData(array('user' => $user));

//GET /
$app->get('/test', function () use ($app) {
	// echo $app->view()->helper();
 //    try {
	// 	$conn = new Mongo();
	// 	$coll = $conn->myblogsite->articles;
	// } catch(MongoConnectionException $e) {
	// 	die("Failed to connect to database " . $e->getMessage());
	// }

	// $cursor = $coll->find(array('comments.name' => 'Bob'));
	// die('<pre>' . print_r(iterator_to_array($cursor, true), 1) . '</pre>');
	// $app->render('test.html', array('name' => $app));
	// require 'lib/mysql.php';
	// require_once 'lib/customer.php';
	// $customer = new Customer();

	// $customer->first_name = 'Frideric';
	// $customer->last_name = 'Bastiat';
	// $customer->email = 'mario@xx.pl';
	// $customer->date_of_birth = '1919-09-25';
	
	// $customer->save();
	// var_dump($customer->id);
	// echo $customer->date_of_birth;

	// die('<pre>' . print_r($customer) . '</pre>');
});


//GET /aggregate
$app->get('/aggregate', function() use ($app) {
	require 'lib/mysql.php';
	$sql = 'SELECT name, DATE(time_of_sales) as date_of_sales, SUM(units_sold) as total_units_sold '
		  .'FROM sales s INNER JOIN products p ON (p.id = s.product_id) '
		  .'GROUP BY product_id, DATE(time_of_sales)';

	$result = $db->get_results($sql);
	$sales_by_date = array();
	foreach($result as $row) {
		$date = $row->date_of_sales;
	    $product = $row->name;
	    $total_sold = $row->total_units_sold;
	    $sales_per_product = (isset($sales_by_date[$date])) ? $sales_by_date[$date] : array();
		$sales_per_product[$product] = $total_sold;
		$sales_by_date[$date] = $sales_per_product;
	}

	$mongodb = DBConnection::init();
	$collection = $mongodb->getCollection('daily_sales');
	foreach($sales_by_date as $date => $sales) {
		$doc = array(
			'sales_date' => new MongoDate(strtotime($date)),
			'items' => array()
		);

		foreach($sales as $product => $units_sold) {
			$doc['items'][$product] = $units_sold;
		}

		$collection->insert($doc);
	}
});

//GET /sales
$app->get('/sales', function() use ($app) {

});

//GET /analytics
$app->get('/analytics', function() use ($app) {

	$map = "function() { emit(this.query_params.id, {count: 1,".
		"resp_time: this.response_time_ms}) }";
	
	$reduce = "function(key, values) { ".
		"var total_count = 0;".
		"var total_resp_time = 0;".
		"values.forEach(function(doc) {".
			"total_count += doc.count;".
			"total_resp_time += doc.resp_time;".
		"});".
		"return {count: total_count, resp_time: total_resp_time};".
	"}";

	$finalize = "function(key, doc) {".
		"doc.avg_resp_time = doc.resp_time / doc.count;".
		"return doc;".
	"}";
	$db = DBConnection::init()->database;
	$db->command(array(
		'mapreduce' => 'access_log',
		'map' => new MongoCode($map),
		'reduce' => new MongoCode($reduce),
		'query' => array('page' => '/articles/:id', 'method' => 'GET',
						'viewed_at' => array('$gt' => new MongoDate(strtotime('-7 days')))),
		'finalize' => new MongoCode($finalize),
		'out' => 'page_views_last_week'
	));

	$results = DBConnection::init()->getCollection('page_views_last_week')->find(); //->sort(array('value.count' => -1));
	// die(var_dump(iterator_to_array($results)));
	$app->render('analytics.html', array('logs' => iterator_to_array($results)));
});


//GET /profile
$app->get('/profile', function() use ($app) {

	$app->render('profile.html');
});


//GET /login
$app->get('/login', function() use ($app) {

	$app->render('login.html', array());
});


//POST /login
$app->post('/login', function() use ($app, $user) {
	$username = $app->request()->post('id_username');
	$password = $app->request()->post('id_password');

	if($user->authenticate($username, $password)) {
		$app->flash('success', 'You have successfully logged in.');
		$app->redirect('/blog/profile');
	} else {
		$app->flash('error', 'Incorrect login details.');
		$app->redirect('/blog/login');
	}
});


//GET /logout
$app->get('/logout', function() use ($app, $user) {
	$user->logout();

	$app->flash('success', 'You have successfully logged out.');
	$app->redirect('/blog/login');
});


//PUT /articles/:id/comments - insert new comment for article
$app->put('/articles/:article_id/comments', function ($article_id) use ($app) {
	$coll = DBConnection::init()->getCollection('articles');

	$comment = array(
		'name' => $app->request()->post('id_name'),
		'email' => $app->request()->post('id_email'),
		'comment' => $app->request()->post('id_comment'),
		'posted_at' => new MongoDate()
	);

	$coll->update(array('_id' => new MongoId($article_id)), array(
		'$push' => array('comments' => $comment)
	));

	$app->flash('success', 'Comment saved successfully for article ID: ' . $article_id);
	$app->redirect('/blog/articles/' . $article_id);
})->name('comments');


//GET /articles - get article list
$app->get('/articles', function () use ($app) {
	$coll = DBConnection::init()->getCollection('articles');

	$cursor = $coll->find();
	$paginator = new Paginator($cursor, 10);
	$cursor->sort(array('created_at' => -1))
		->skip($paginator->get_skip())
		->limit(10);
	$articles = iterator_to_array($cursor);

	$app->render('article_list.html', array(
		'articles' => $articles, 'paginator' => $paginator)
	);
});


//GET /articles/new
$app->get('/articles/new', $authorize('logged'), function () use ($app) {
	$app->render('article_form.html');
});


//PUT /articles - create new article post
$app->put('/articles', $authorize('logged'), function () use ($app, $user) {
	$coll = DBConnection::init()->getCollection('articles');
	try {
		// some dummy validating
		$tags_clean = array_filter(array_map(function($tag) {
			return trim($tag);
		}, explode(',', $app->request()->post('id_tags'))));

		$time = new MongoDate();
		$article = array(
			'title' => $app->request()->post('id_title'),
			'content' => $app->request()->post('id_content'),
			'created_at' => $time,
			'updated_at' => $time,
			'tags' => $tags_clean,
			'author_id' => $user->_id
		);
		$coll->insert($article);
	} catch(MongoException $e) {
		die('Failed to insert data ' . $e->getMessage());
	}

	$app->flash('success', 'Article saved successfully. ID: ' . $article['_id']);
	$app->redirect('/blog/dashboard');
});


//GET /articles/:id
$app->get('/articles/:id', function ($id) use ($app) {
	$coll = DBConnection::init()->getCollection('articles');
	$article = $coll->findOne(array('_id' => new MongoId($id)));

	$app->render('article_details.html', array('article' => $article));
})->name('view');


//POST /articles/:id - update article
$app->post('/articles/:id', $authorize('edit.article'), function ($id) use ($app) {
	$coll = DBConnection::init()->getCollection('articles');
	try {
		// some dummy validating
		$tags_clean = array_filter(array_map(function($tag) {
			return trim($tag);
		}, explode(',', $app->request()->post('id_tags'))));

		$article = array(
			'title' => $app->request()->post('id_title'),
			'content' => $app->request()->post('id_content'),
			'updated_at' => new MongoDate(),
			'tags' => $tags_clean
		);;

		$coll->update(array('_id' => new MongoId($id)), array(
			'$set' => $article
		));
	} catch(MongoException $e) {
		die('Failed to update data ' . $e->getMessage());
	}

	$app->flash('success', 'Article updated successfully. ID: ' . $id);
	$app->redirect('/blog/dashboard');
});


//DELETE /articles/:id - delete article
$app->delete('/articles/:id', $authorize('edit.article'), function ($id) use ($app) {
	$coll = DBConnection::init()->getCollection('articles');
	$coll->remove(array('_id' => new MongoId($id)));

	$app->flash('success', 'Article deleted successfully. ID: ' . $id);
	$app->redirect('/blog/dashboard');
})->name('delete');


//GET /articles/edit/:id
$app->get('/articles/:id/edit', $authorize('edit.article'), function ($id) use ($app) {
	$article = DBConnection::init()
		->getCollection('articles')
		->findOne(array('_id' => new MongoId($id)));
	// die(var_dump($article));
	$app->render('article_form.html', array('article' => $article));
})->name('edit');


//GET /dashboard
$app->get('/dashboard', function () use ($app, $user) {
	$coll = DBConnection::init()->getCollection('articles');
	$cursor = $coll->find(array('author_id' => new MongoId($user->_id)), array('title', 'created_at', 'updated_at'));
	$paginator = new Paginator($cursor, 5);
	// $coll->update(array(), array('$set' => array('updated_at' => new MongoDate())), array('multiple' => True));
	// die('<pre>' . print_r(iterator_to_array($cursor, true), 1) . '</pre>');

	$cursor->sort(array('created_at'=>-1))->skip($paginator->get_skip())->limit(5);
	
	$articles = iterator_to_array($cursor);
	$app->render('dashboard.html', array('articles' => $articles, 'paginator' => $paginator));
});

$app->run();

$end = microtime();
$data = array('response_time_ms' => ($end - $start) * 1000);
$logger = new Logger($app);
$logger->logRequest($data);
