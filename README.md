Hybrid XML Parser
=================
Класс для разбора больших XML-файлов без загрузки их в память.
Схема работы проста: файл проходится XMLReader'ом, для каждого интересующего нас элемента XML вызывается
предварительно заданный обработчик, которому в качестве аргумента передаётся содержимое этого элемента
в виде SimpleXMLElement.

Выглядит это как-то так:

```php
<?php
	use Symfony\Component\DomCrawler\Crawler;

	$parser = new HybridXMLParser;
	$parser
		// Вешаем обработчик на путь в XML
		->bind('/FictionBook/description/title-info/author', function(Crawler $author, $parser) {
			print_r($author);
		})
		// И ещё один
		->bind('/FictionBook/description/title-info/translator', function(Crawler $translator, $parser) {
			print_r($translator);
			// Так можно немедленно завершить парсинг
			$parser->stop();
		})
		// Запускаем
		->process('somebook.fb2')
		->process('anotherbook.fb2');

```

В качестве обработчика можно указывать всё, для чего is_callable() возвращает true.
