<?php

class Secret_Middleware extends Slim_Middleware {
	public function call() {
		$app = $this->app;
		$req = $app->request();
		$res = $app->response();
		echo var_dump($app->router());

		$this->next->call();

		echo var_dump($app->router());

	}
}