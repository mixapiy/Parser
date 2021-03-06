<?php

  declare(strict_types=1);

  namespace Test\Xparse\Parser;

  use GuzzleHttp\Psr7\Response;
  use PHPUnit\Framework\TestCase;
  use Xparse\ElementFinder\ElementFinder;
  use Xparse\Parser\ElementFinderFactory;

  class ElementFinderFactoryTest extends TestCase {

    /**
     * @return array
     */
    public function getDifferentCharsetStylesDataProvider() : array {
      return [
        [
          '<body></body>',
          '',
          ['content-type' => 'df'],
        ],
        [
          iconv('UTF-8', 'WINDOWS-1251', '<meta http-equiv="Content-Type" content=\'text/html; charset=windows-1251\' /><body>Текст текст text</body>'),
          'Текст текст text',
        ],
        [
          iconv('UTF-8', 'WINDOWS-1251', '<meta charset=\' windows-1251 \'><body>Текст текст text</body>'),
          'Текст текст text',
        ],
        [
          iconv('UTF-8', 'WINDOWS-1251', '<meta http-equiv="Content-Type" content="text/html; charset=test-as225" /><body>Текст текст text</body>'),
          '  text',
        ],
        [
          iconv('UTF-8', 'WINDOWS-1251', '<meta http-equiv="Content-Type" content="text/html; charset=windows-1251" /><body>Текст текст text</body>'),
          'Текст текст text',
        ],
        [
          iconv('UTF-8', 'WINDOWS-1251', '<meta charset="windows-1251"><body>Текст текст text</body>'),
          'Текст текст text',
        ],
        [
          '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><body>Текст текст text</body>',
          'Текст текст text',
        ],
        [
          '<meta http-equiv="Content-Type" content="text/html; charset=utf8" /><body>Текст текст text</body>',
          'Текст текст text',
        ],
        [
          '<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-5" /><body>Текст текст text</body>',
          'Текст текст text',
          ['content-type' => 'text/html; charset=utf-8'],
        ],
        [
          iconv('UTF-8', 'WINDOWS-1251', '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><body>Текст текст text</body>'
          ),
          'Текст текст text',
          ['content-type' => 'text/html; charset=windows-1251'],
        ],
      ];
    }


    /**
     * @dataProvider getDifferentCharsetStylesDataProvider
     * @param array $headers
     * @param string $html
     * @param string $bodyText
     */
    public function testDifferentCharsetStyles($html, $bodyText, array $headers = []) {
      $response = new Response(200, $headers, $html);
      $page = (new ElementFinderFactory())->create($response);
      $pageBodyText = $page->content('//body')->first();
      self::assertInstanceOf(ElementFinder::class, $page);
      self::assertEquals($bodyText, $pageBodyText);
    }

  }
