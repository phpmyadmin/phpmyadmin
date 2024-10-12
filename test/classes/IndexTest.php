<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Index;

/**
 * @covers \PhpMyAdmin\Index
 */
class IndexTest extends AbstractTestCase
{
    /** @var array */
    private $params = [];

    /**
     * Configures parameters.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->params['Schema'] = 'PMA_Schema';
        $this->params['Table'] = 'PMA_Table';
        $this->params['Key_name'] = 'PMA_Key_name';
        $this->params['Index_choice'] = 'PMA_Index_choice';
        $this->params['Comment'] = 'PMA_Comment';
        $this->params['Index_comment'] = 'PMA_Index_comment';
        $this->params['Non_unique'] = 'PMA_Non_unique';
        $this->params['Packed'] = 'PMA_Packed';

        //test add columns
        $column1 = [
            'Column_name' => 'column1',
            'Seq_in_index' => 'index1',
            'Collation' => 'Collation1',
            'Cardinality' => 'Cardinality1',
            'Null' => 'null1',
        ];
        $column2 = [
            'Column_name' => 'column2',
            'Seq_in_index' => 'index2',
            'Collation' => 'Collation2',
            'Cardinality' => 'Cardinality2',
            'Null' => 'null2',
        ];
        $column3 = [
            'Column_name' => 'column3',
            'Seq_in_index' => 'index3',
            'Collation' => 'Collation3',
            'Cardinality' => 'Cardinality3',
            'Null' => 'null3',
        ];
        $this->params['columns'][] = $column1;
        $this->params['columns'][] = $column2;
        $this->params['columns'][] = $column3;
    }

    /**
     * Test for Constructor
     */
    public function testConstructor(): void
    {
        $index = new Index($this->params);
        self::assertSame('PMA_Index_comment', $index->getComment());
        self::assertSame('PMA_Comment', $index->getRemarks());
        self::assertSame('PMA_Index_choice', $index->getChoice());
        self::assertSame('PMA_Packed', $index->getPacked());
        self::assertSame('PMA_Non_unique', $index->getNonUnique());
        self::assertStringContainsString('PMA_Comment', $index->getComments());
        self::assertStringContainsString('PMA_Index_comment', $index->getComments());
        self::assertSame('PMA_Index_choice', $index->getChoice());
    }

    /**
     * Test for isUnique
     */
    public function testIsUniquer(): void
    {
        $this->params['Non_unique'] = '0';
        $index = new Index($this->params);
        self::assertTrue($index->isUnique());
        self::assertSame('Yes', $index->isUnique(true));
    }

    /**
     * Test for add Columns
     */
    public function testAddColumns(): void
    {
        $index = new Index();
        $index->addColumns($this->params['columns']);
        self::assertTrue($index->hasColumn('column1'));
        self::assertTrue($index->hasColumn('column2'));
        self::assertTrue($index->hasColumn('column3'));
        self::assertSame(3, $index->getColumnCount());
    }

    /**
     * Test for get Name & set Name
     */
    public function testName(): void
    {
        $index = new Index();
        $index->setName('PMA_name');
        self::assertSame('PMA_name', $index->getName());
    }

    public function testColumns(): void
    {
        $index = new Index();
        $index->addColumns($this->params['columns']);

        $index_columns = $index->getColumns();
        $index_column = $index_columns['column1'];
        self::assertSame('column1', $index_column->getName());
        self::assertSame('index1', $index_column->getSeqInIndex());
        self::assertSame('Collation1', $index_column->getCollation());
        self::assertSame('Cardinality1', $index_column->getCardinality());
    }
}
