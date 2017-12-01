<?php
	// error_reporting(E_ALL);
	// ini_set('display_errors', true);
	
	class Database
	{
		protected $pdo;

		function __construct($params)
		{
			$dbConfig = array(
				'host'       => '<DB Host>',
				'dbName'     => '<DB Name> ',
				'dbUser'     => '<DB User>',
				'dbPassword' => '<DB Password>'
			);

			if ($params['adLogin'] == 'admin' && $params['adPwd'] == 'ajsdhk1AD3Aa4sn5si') {
				$this->pdo = $this->getConnection($dbConfig);
				$this->isError = false;
			}else{
				$this->isError = true;
			}
		}

		private function getConnection($config){
			try {
				$pdo = new PDO('mysql:host='.$config['host'].';dbname='.$config['dbName'].'', $config['dbUser'], $config['dbPassword']);
				return $pdo;
			} catch (PDOException $e) {
			    print "Connection Error!: " . $e->getMessage() . " \n";
			    die();
			}
		}

		public function execute($sql, $condition = array()){
			try{
				$res = $this->pdo->prepare($sql);
				if (!empty($condition)) {
					foreach ($condition as $key => $value) {
						$res->bindParam(':'.$key, $value);
					}
				}
				$res->execute();
				$records = $res->fetchAll(PDO::FETCH_ASSOC);

			} catch (PDOException $e) {
			    print "Mysql Error!: " . $e->getMessage() . "<br/>";
			    die();
			}
			return $records;
			// return array();
		}
	}