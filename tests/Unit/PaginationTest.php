<?php

use App\Http\Resources\Pagination;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

class PaginationTest extends PHPUnitTestCase
{
    public function testItReturnsTheTotalAndCurrentPageItems(): void
    {
        $items = new Collection([
            ['id' => 3, 'name' => 'Utilities'],
            ['id' => 4, 'name' => 'Internet'],
        ]);
        $paginator = new LengthAwarePaginator($items, 5, 2, 2);
        $resource = new Pagination($paginator);

        $this->assertSame([
            'total' => 5,
            'data' => $items->all(),
        ], $resource->toArray(Request::create('/expenses?page=2')));
    }
}
