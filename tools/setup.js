'use strict';

const { systemCmd } = require('./lib/system.js');

(async () => {
  try {
    await systemCmd('git submodule init');
    await systemCmd('git submodule update');
    await systemCmd('npm ci');
  } catch (err) {
    console.log(err);
  }
})();
