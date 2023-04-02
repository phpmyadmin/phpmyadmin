<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Index;

/** @covers \PhpMyAdmin\Index */
class IndexTest extends AbstractTestCase
{
    /** @var mixed[] */
    private array $params = [];

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
            'Seq_in_index' => '1',
            'Collation' => 'Collation1',
            'Cardinality' => '1',
            'Null' => 'null1',
        ];
        $column2 = [
            'Column_name' => 'column2',
            'Seq_in_index' => '2',
            'Collation' => 'Collation2',
            'Cardinality' => '2',
            'Null' => 'null2',
        ];
        $column3 = [
            'Column_name' => 'column3',
            'Seq_in_index' => '3',
            'Collation' => 'Collation3',
            'Cardinality' => '3',
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
        $this->assertEquals(
            'PMA_Index_comment',
            $index->getComment(),
        );
        $this->assertEquals(
            'PMA_Comment',
            $index->getRemarks(),
        );
        $this->assertEquals(
            'PMA_Index_choice',
            $index->getChoice(),
        );
        $this->assertEquals(
            'PMA_Packed',
            $index->getPacked(),
        );
        $this->assertEquals(
            'PMA_Non_unique',
            $index->getNonUnique(),
        );
        $this->assertStringContainsString(
            'PMA_Comment',
            $index->getComments(),
        );
        $this->assertStringContainsString(
            'PMA_Index_comment',
            $index->getComments(),
        );
        $this->assertEquals(
            'PMA_Index_choice',
            $index->getChoice(),
        );
    }

    /**
     * Test for isUnique
     */
    public function testIsUniquer(): void
    {
        $this->params['Non_unique'] = '0';
        $index = new Index($this->params);
        $this->assertTrue(
            $index->isUnique(),
        );
        $this->assertEquals(
            'Yes',
            $index->isUnique(true),
        );
    }

    /**
     * Test for add Columns
     */
    public function testAddColumns(): void
    {
        $index = new Index();
        $index->addColumns($this->params['columns']);
        $this->assertTrue($index->hasColumn('column1'));
        $this->assertTrue($index->hasColumn('column2'));
        $this->assertTrue($index->hasColumn('column3'));
        $this->assertEquals(
            3,
            $index->getColumnCount(),
        );
    }

    /**
     * Test for get Name & set Name
     */
    public function testName(): void
    {
        $index = new Index();
        $index->setName('PMA_name');
        $this->assertEquals(
            'PMA_name',
            $index->getName(),
        );
    }

    public function testColumns(): void
    {
        $index = new Index();
        $index->addColumns($this->params['columns']);

        $indexColumns = $index->getColumns();
        $indexColumn = $indexColumns['column1'];
        $this->assertEquals(
            'column1',
            $indexColumn->getName(),
        );
        $this->assertEquals(
            '1',
            $indexColumn->getSeqInIndex(),
        );
        $this->assertEquals(
            'Collation1',
            $indexColumn->getCollation(),
        );
        $this->assertEquals(
            '1',
            $indexColumn->getCardinality(),
        );
    }
}
