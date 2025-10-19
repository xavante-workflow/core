<?php

namespace Xavante\Actions;

interface Actionable
{
    public function configure(mixed ...$args): void;
    public function execute(mixed ...$args): void;
}
