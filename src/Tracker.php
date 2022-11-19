<?php

/**
 *
 */

namespace HexMakina\Crudites;

class Tracker
{

  // logs all queries for debug and optimizing
  // null means tracking is disabled
    private ?array $tracks = null;

    private bool $active = false;


    public function track(): void
    {
        if ($this->active) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 3);
            $meta = [];
            $meta['statement'] = $backtrace[1]['args'][0] ?? '';
            $meta['function']  = $backtrace[1]['function'] ?? '';
            $meta['arguments'] = json_encode($backtrace[1]['args'] ?? '');
            $this->tracks[hrtime(true)] = $meta;
        }
    }

    public function tracks(): ?array
    {
        return $this->tracks;
    }

    public function activate(): void
    {
        $this->active = true;
    }
}
