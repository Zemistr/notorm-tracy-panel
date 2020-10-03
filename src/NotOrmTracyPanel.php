<?php

declare(strict_types=1);


use Nette\Utils\Strings;
use Tracy\Debugger;
use Tracy\IBarPanel;
use Yep\Reflection\ReflectionClass;

class NotOrmTracyPanel implements IBarPanel
{

	/** @var int */
	public static $maxQueries = 200;

	/** @var int maximum SQL length */
	public static $maxLength = 500;

	/** @var bool */
	public $disabled = false;

	/** @var bool|string explain queries? */
	public $explain = true;

	/** @var int */
	private $count = 0;

	/** @var mixed[][] (int => {$query, $parameters, null}) */
	private $queries = [];

	/** @var string */
	private $platform = '';

	/** @var float */
	private $totalTime = 0;

	/** @var \NotORM|null */
	private $notOrm;

	/** @var \PDO|null */
	private $pdo;


	public static function simpleInit(\NotORM $notOrm, ?\PDO $pdo = null): void
	{
		$self = new self;
		$self->setNotOrm($notOrm);

		if ($pdo) {
			$self->setPdo($pdo);
		}

		$notOrmReflection = ReflectionClass::from($notOrm);
		$notOrmReflection->setPropertyValue(
			'debug',
			static function ($query, $parameters) use ($self): void {
				$self->logQuery($query, $parameters);
				$self->startQueryTimer($self->getIndex());
			}
		);

		$notOrmReflection->setPropertyValue(
			'debugTimer',
			static function () use ($self): void {
				$self->stopQueryTimer($self->getIndex());
			}
		);

		$self->setPlatform($pdo->getAttribute(PDO::ATTR_DRIVER_NAME));

		Debugger::getBar()->addPanel($self);
	}


	public static function dump(string $sql): string
	{
		$keywords1 = 'CREATE\s+TABLE|CREATE(?:\s+UNIQUE)?\s+INDEX|SELECT|UPDATE|INSERT(?:\s+INTO)?|REPLACE(?:\s+INTO)?|DELETE|FROM|WHERE|HAVING|GROUP\s+BY|ORDER\s+BY|LIMIT|SET|VALUES|LEFT\s+JOIN|INNER\s+JOIN|TRUNCATE';
		$keywords2 = 'ALL|DISTINCT|DISTINCTROW|AS|USING|ON|AND|OR|IN|IS|NOT|NULL|LIKE|TRUE|FALSE|INTEGER|CLOB|VARCHAR|DATETIME|TIME|DATE|INT|SMALLINT|BIGINT|BOOL|BOOLEAN|DECIMAL|FLOAT|TEXT|VARCHAR|DEFAULT|AUTOINCREMENT|PRIMARY\s+KEY';

		// insert new lines
		$sql = " $sql ";
		$sql = Strings::replace($sql, "#(?<=[\\s,(])($keywords1)(?=[\\s,)])#", "\n\$1");
		if (strpos($sql, 'CREATE TABLE') !== false) {
			$sql = Strings::replace($sql, '#,\s+#i', ", \n");
		}

		// reduce spaces
		$sql = Strings::replace($sql, '#[ \t]{2,}#', ' ');

		$sql = wordwrap($sql, 100);
		$sql = htmlspecialchars($sql);
		$sql = Strings::replace($sql, "#([ \t]*\r?\n){2,}#", "\n");
		$sql = Strings::replace($sql, '#VARCHAR\\(#', 'VARCHAR (');

		// syntax highlight
		$sql = Strings::replace(
			$sql,
			"#(/\\*.+?\\*/)|(\\*\\*.+?\\*\\*)|(?<=[\\s,(])($keywords1)(?=[\\s,)])|(?<=[\\s,(=])($keywords2)(?=[\\s,)=])#s",
			static function (array $matches): string {
				if (!empty($matches[1])) { // comment
					return '<em style="color:gray">' . $matches[1] . '</em>';
				}
				if (!empty($matches[2])) { // error
					return '<strong style="color:red">' . $matches[2] . '</strong>';
				}
				if (!empty($matches[3])) { // most important keywords
					return '<strong style="color:blue">' . $matches[3] . '</strong>';
				}
				if (!empty($matches[4])) { // other keywords
					return '<strong style="color:green">' . $matches[4] . '</strong>';
				}

				return ''; // parse error
			}
		);
		$sql = trim($sql);

		return '<pre class="dump">' . $sql . '</pre>' . "\n";
	}


	public function getNotOrm(): ?\NotORM
	{
		return $this->notOrm;
	}


	public function setNotOrm(\NotORM $notOrm): void
	{
		$this->notOrm = $notOrm;
	}


	public function getPdo(): ?\PDO
	{
		if ($this->pdo === null && $this->notOrm !== null) {
			$this->pdo = ReflectionClass::from($this->notOrm)->getPropertyValue('connection');
		}

		return $this->pdo;
	}


	public function setPdo(\PDO $pdo): void
	{
		$this->pdo = $pdo;
	}


	public function getPlatform(): string
	{
		return $this->platform;
	}


	public function setPlatform(string $platform): void
	{
		$this->platform = $platform;
	}


	public function getId(): string
	{
		return 'NotORM';
	}


	public function getTab(): string
	{
		return '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAAXNSR0IArs4c6QAAAHpJREFUOMvVU8ENgDAIBON8dgY7yU3SHTohfoQUi7FGH3pJEwI9oBwl+j1YDRGR8AIzA+hiAIxLsoOW1R3zB9Cks1VKmaQWXz3wHWEJpBbilF3wivxKB9OdiUfDnJ6Q3RNGyWp3MraytbKqjADkrIvhPYgSDG3itz/TBsqre3ItA1W8AAAAAElFTkSuQmCC" alt="NotORM" />'
			. ($this->count . ' ' . ($this->count === 1 ? 'query' : 'queries'))
			. ($this->totalTime ? sprintf(' / %0.3f ms', $this->totalTime * 1000) : '');
	}


	/**
	 * @return string HTML code for Debugbar detail
	 */
	public function getPanel(): ?string
	{
		$this->disabled = true;

		if (!$this->count) {
			return null;
		}

		$s = '<style>';
		$s .= '#tracy-debug-panel-NotOrmTracyPanel table { width:100% }';
		$s .= '#tracy-debug-panel-NotOrmTracyPanel pre.tracy-dump span { color:#c16549 }';
		$s .= '#tracy-debug-panel-NotOrmTracyPanel .tracy-alt td.notorm-sql { background: #f5f5f5 }';
		$s .= '#tracy-debug #tracy-debug-panel-NotOrmTracyPanel td.notorm-sql { background: #fff }';
		$s .= '</style>';
		$s .= '<h1>'
			. (($this->count === 1 ? 'Query' : 'Queries') . ': ' . $this->count)
			. ($this->totalTime ? sprintf(' / %0.3f ms', $this->totalTime * 1000) : '')
			. '</h1>';
		$s .= '<div class="tracy-inner">';
		$s .= '<table>';
		$s .= '<tr><th colspan="3">Connection Platform</th></tr>';
		$s .= '<tr><td colspan="3">' . $this->getPlatform() . '</td></tr>';
		$s .= '<tr><th>Time&nbsp;ms</th><th>SQL&nbsp;Statement</th><th>Params</th></tr>';

		if ($this->queries) {
			foreach ($this->queries as [$sql, $params, $time]) {
				$explain = null;
				if ($this->explain && preg_match('#\s*\(?\s*SELECT\s#iA', $sql) && ($connection = $this->getPdo())) {
					try {
						$cmd = is_string($this->explain) ? $this->explain : 'EXPLAIN';
						$sth = $connection->prepare($cmd . ' ' . $sql);
						$sth->execute($params);
						$explain = $sth->fetchAll();
					} catch (\PDOException $e) {
					}
				}

				$s .= '<tr>';
				$s .= '<td>' . ($time ? sprintf('%0.3f', $time * 1000) : '');

				static $counter;

				if ($explain) {
					$counter++;
					$s .= "<br /><a class='tracy-toggle tracy-collapsed' href='#notorm-tracy-DbConnectionPanel-row-$counter'>explain</a>";
				}

				$s .= '</td>';
				$s .= '<td class="notorm-sql">' . self::dump($sql);

				if ($explain) {
					$s .= "<table id='notorm-tracy-DbConnectionPanel-row-$counter' class='tracy-collapsed'><tr>";
					foreach ($explain[0] as $col => $foo) {
						$s .= '<th>' . htmlspecialchars($col) . '</th>';
					}
					$s .= '</tr>';
					foreach ($explain as $row) {
						$s .= '<tr>';
						foreach ($row as $col) {
							$s .= '<td>' . htmlspecialchars($col) . '</td>';
						}
						$s .= '</tr>';
					}
					$s .= '</table>';
				}

				$s .= '</td>';
				$s .= '<td>' . Debugger::dump($params, true) . '</td>';
				$s .= '</tr>';
			}
		} else {
			$s .= '<tr><td colspan="3">No SQL logs found</td></tr>';
		}

		$s .= '</table>';

		if (count($this->queries) < $this->count) {
			$s .= '<p>...and more</p>';
		}

		$s .= '</div>';

		return $s;
	}


	/**
	 * @param mixed[] $parameters
	 */
	public function logQuery(string $query, array $parameters = []): void
	{
		if ($this->disabled) {
			return;
		}

		$this->count++;

		if ($this->count < self::$maxQueries) {
			$this->queries[$this->count - 1] = [$query, $parameters, null];
		}
	}


	public function startQueryTimer(int $index): void
	{
		if (isset($this->queries[$index])) {
			Debugger::timer(__CLASS__ . ':' . $index);
		}
	}


	public function stopQueryTimer(int $index): void
	{
		if (isset($this->queries[$index])) {
			$time = Debugger::timer(__CLASS__ . ':' . $index);
			$this->totalTime += $time;
			$this->queries[$index][2] = $time;
		}
	}


	public function getCount(): int
	{
		return $this->count;
	}


	public function getIndex(): int
	{
		return $this->count - 1;
	}
}
