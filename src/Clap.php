<?php

namespace Laugharn\Clap;

use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory as Validator;

class Clap
{
    var $loader, $translator, $request, $validator;

    function __construct()
    {
        $this->loader = new FileLoader(new Filesystem, 'lang');
        $this->translator = new Translator($this->loader, 'en');
        $this->request = Request::createFromGlobals();
        $this->validator = $this->initializeValidator();
    }

    function getRules($input)
    {
        $rules = collect($input)->filter(function($value, $key) {
            return ends_with($key, '_rules');
        })->map(function($value, $key) {
            return [str_replace('_rules', '', $key), $value];
        })->keyBy(function($item) {
            return $item[0];
        })->map(function($item) {
            return $item[1];
        })->all();

        return $rules;
    }

    function getValidator()
    {
        return $this->validator;
    }

    function initializeValidator()
    {
        $validator = new Validator($this->translator, new Container);
        $validator->extend('phone', function($attribute, $value, $parameters, $validator) {
            preg_match('/\d+/', $value, $matches);

            if(strlen($matches[0]) == 10) {
                return true;
            }
        });

        return $validator;
    }

    function setValidator($validator)
    {
        $this->validator = $validator;
    }

    function validate()
    {
        $input = $this->request->input();
        $rules = $this->getRules($input);

        return $this->validator->make($rules, $input);
    }
}
