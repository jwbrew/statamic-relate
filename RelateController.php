<?php

namespace Statamic\Addons\Relate;

use Statamic\Extend\Controller;

class RelateController extends Controller
{
    /**
     * Maps to your route definition in routes.yaml
     *
     * @return mixed
     */
    public function index()
    {
        return $this->view('index');
    }
}
