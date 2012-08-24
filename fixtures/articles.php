<?php

require '../lib/dbconnection.php';

$titles = array(
	'Nature always does smth..',
	'Adding mapower to late project always delays it',
	'No one expects Spanish incvisition',
	'Always draw your curves, the plot your reading',
	'Software bug are hard to detect, except may be the end user'
);

$authors = array(
	'Luke Skywalker', 'Leia Organa', 'Han Solo', 'Darth Vader',
	'Spock', 'James Kirk', 'Hikaru Sulu', 'Nyota Uhara'
);

$description = array(
	'Lorem ipsum dolor sit amet, consectetur adipisicing elit',
	'Proin nibh augue, suscipit a, scelerisque sed, lacinia in, mi.',
	'Etiam pellentesque aliquet tellus. Phasellus pharetra nulla ac diam',
	'Quisque semper justo at risus. Donec venenatis, turpis vel hendrerit interdum, dui ligula ultricies purus, sed posuere libero dui id orci'
);

$categories = array(
	'Electronics', 'Mathemtics', 'Programming', 'Data Structures',
	'Algorithms', 'Computer Networking'
);

$tags = array(
	'programming', 'testing', 'webdesign', 'tutorial', 'howto',
	'version-control', 'nosql', 'algorithms', 'engineering',
	'software', 'hardware', 'security'
);

function getRandomArrayItem($array) {
	$len = count($array);
	$randIndex = mt_rand(0, $len - 1);
	return $array[$randIndex];
}

function getRandomTimestamp() {
	$randDigit = mt_rand(0, 6) * -1;
	return strtotime($randDigit . ' day');
}

function createDoc() {
	global $titles, $authors, $categories, $tags;
	$title = getRandomArrayItem($titles);
	$author = getRandomArrayItem($authors);
	$category = getRandomArrayItem($categories);

	$articleTags = array();
	$numOfTags = rand(1, 5);
	for($j = 0; $j < $numOfTags; $j++) {
		$tag = getRandomArrayItem($tags);
		if(!in_array($tag, $articleTags)) {
			array_push($articleTags, $tag);
		}
	}

	$rating = mt_rand(1, 10);
	$publishedAt = new MongoDate(getRandomTimestamp());
	return array(
		'title' => $title,
		'author' => $author,
		'category' => $category,
		'tags' => $articleTags,
		'rating' => $rating,
		'published_at' => $publishedAt
	);
}

$mongo = DBConnection::init();
$coll = $mongo->getCollection('sample_articles');
echo 'Generating sample data...<br />';
for($i = 0; $i < 1000; $i++) {
	$doc = createDoc();
	$coll->insert($doc);
}

echo 'Finished!';
