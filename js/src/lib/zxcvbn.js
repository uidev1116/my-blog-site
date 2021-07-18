const zxcvbn = require('zxcvbn');

export default (item) => {
  const input = item.querySelector(ACMS.Config.passwordStrengthInputMark);
  const meter = item.querySelector(ACMS.Config.passwordStrengthMeterMark);
  const label = item.querySelector(ACMS.Config.passwordStrengthLabelMark);
  const message = ACMS.Config.passwordStrengthMessage;

  input.addEventListener('input', () => {
    const result = zxcvbn(input.value);
    meter.className = meter.className.replace(new RegExp('\\bjs-result-\\S+', 'gi'), ' ');
    meter.className += ` js-result-${result.score}`;
    label.className = label.className.replace(new RegExp('\\bjs-label-\\S+', 'gi'), ' ');

    if (input.value) {
      label.innerHTML = message[result.score];
      label.className += ` js-label-${result.score}`;
    } else {
      label.innerHTML = '';
    }
  });
};
