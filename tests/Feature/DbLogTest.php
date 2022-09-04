<?php

use Illuminate\Support\Facades\{DB,Schema,Storage,File};
use Illuminate\Support\Collection;


beforeEach(function () {
    $this->logsPath = storage_path('app/dblogs');

    if (File::isDirectory($this->logsPath)) {
        File::deleteDirectory($this->logsPath);
    }
});

it('creates users', function () {
    expect(DB::table('users'))
       ->get()
       ->toHaveCount(100);
});


it('does not log a short query', function () {
    expect(DB::table('users'))->get();    
    
    expect(File::isDirectory($this->logsPath))->toBeFalse();

});

it('logs a long query', function () {
    $users = Collection::times(100000 , fn($number) => ['name' => "User #{$number}"])->toArray();
    
    DB::table('users')->insert($users);

    $files = File::allFiles($this->logsPath);
    
    expect($files)->toHaveCount(1);

    
    expect(File::get($files[0]))
      ->toContain('insert into "users" ("name")')
      ->toContain('\'User #600\')');

});




