# tine phpunit tests

collection of tips & tricks regarding phpunit tests

## make protected function public to ease testing

example: Filemanager_Frontend_HttpTest

~~~php
class Filemanager_Frontend_HttpTest extends TestCase
{
    use GetProtectedMethodTrait;

    [...]

    function testXYZ()
    {
        [...]
        $reflectionMethod = $this->getProtectedMethod(Filemanager_Frontend_Http::class, '_downloadFileNodeByPathOrId');
        $reflectionMethod->invokeArgs($uit, [$file['path'], null]);
    }
~~~
