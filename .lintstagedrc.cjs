module.exports = {
  '*.{ts,vue}': ['eslint --fix'],
  'src/*.{scss,vue,ts,md,json}': ['prettier --write'],
  '*.php': [
    'php vendor-bin/cs-fixer/vendor/php-cs-fixer/shim/php-cs-fixer.phar --config=.php-cs-fixer.dist.php fix',
  ],
  '*Controller.php': [() => 'make openapi', () => 'git add openapi.json'],
}
