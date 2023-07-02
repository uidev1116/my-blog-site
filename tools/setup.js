'use strict';

const { systemCmd } = require('./lib/system.js');

(async () => {
  try {
    await systemCmd('git submodule init');
    await systemCmd('git submodule update');
    await systemCmd('git submodule foreach npm ci');
    await systemCmd('git submodule foreach npm run setup');
    await systemCmd('npm ci');
    await systemCmd('unlink ablogcms/extension/plugins/V2');
    await systemCmd('ln -s ../../../plugins/v2/src ablogcms/extension/plugins/V2');
  } catch (err) {
    console.log(err);
  }
})();
