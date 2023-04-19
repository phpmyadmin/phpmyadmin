<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\ShowGrants;

/** @covers \PhpMyAdmin\ShowGrants */
class ShowGrantsTest extends AbstractTestCase
{
    public function test1(): void
    {
        $showGrants = new ShowGrants('GRANT ALL PRIVILEGES ON *.* TO \'root\'@\'localhost\' WITH GRANT OPTION');
        $this->assertEquals('ALL PRIVILEGES', $showGrants->grants);
        $this->assertEquals('*', $showGrants->dbName);
        $this->assertEquals('*', $showGrants->tableName);
    }

    public function test2(): void
    {
        $showGrants = new ShowGrants('GRANT ALL PRIVILEGES ON `mysql`.* TO \'root\'@\'localhost\' WITH GRANT OPTION');
        $this->assertEquals('ALL PRIVILEGES', $showGrants->grants);
        $this->assertEquals('mysql', $showGrants->dbName);
        $this->assertEquals('*', $showGrants->tableName);
    }

    public function test3(): void
    {
        $showGrants = new ShowGrants(
            'GRANT SELECT, INSERT, UPDATE, DELETE ON `mysql`.`columns_priv` TO \'root\'@\'localhost\'',
        );
        $this->assertEquals('SELECT, INSERT, UPDATE, DELETE', $showGrants->grants);
        $this->assertEquals('mysql', $showGrants->dbName);
        $this->assertEquals('columns_priv', $showGrants->tableName);
    }

    public function test4(): void
    {
        $showGrants = new ShowGrants('GRANT ALL PRIVILEGES ON `cptest\_.`.* TO \'cptest\'@\'localhost\'');
        $this->assertEquals('cptest\_.', $showGrants->dbName);

        $showGrants = new ShowGrants(
            'GRANT ALL PRIVILEGES ON `cptest\_.a.b.c.d.e.f.g.h.i.j.k.'
                . 'l.m.n.o.p.q.r.s.t.u.v.w.x.y.z`.* TO \'cptest\'@\'localhost\'',
        );
        $this->assertEquals('cptest\_.a.b.c.d.e.f.g.h.i.j.k.l.m.n.o.p.q.r.s.t.u.v.w.x.y.z', $showGrants->dbName);
    }
}
