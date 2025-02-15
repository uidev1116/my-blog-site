const loaderUtils = require('loader-utils');

module.exports = function (source) {
  const name = loaderUtils.interpolateName(this, '[name]', { source });
  const options = this.getOptions();

  if (options.target.test(name)) {
    return source.replace(options.pattern, options.replace);
  }
  return source;
};
