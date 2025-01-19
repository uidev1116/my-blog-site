const zxcvbn = require('zxcvbn');

export default (item) => {
  const { passwordStrengthInputMark, passwordStrengthMeterMark, passwordStrengthLabelMark, passwordStrengthMessage } =
    ACMS.Config;
  const input = item.querySelector(passwordStrengthInputMark);
  const meter = item.querySelector(passwordStrengthMeterMark);
  const label = item.querySelector(passwordStrengthLabelMark);

  if (input === null) {
    return;
  }

  input.addEventListener('input', (event) => {
    const currentScore = event.target.getAttribute('data-score');
    if (event.target.value === '') {
      // 未入力の場合は、初期化
      meter.classList.remove(`js-result-${currentScore}`);
      label.classList.remove(`js-label-${currentScore}`);
      label.innerHTML = '';
      return;
    }

    const { score } = zxcvbn(event.target.value);

    if (meter !== null) {
      meter.classList.remove(`js-result-${currentScore}`);
      meter.classList.add(`js-result-${score}`);
    }
    if (label !== null) {
      label.classList.remove(`js-label-${currentScore}`);
      label.classList.add(`js-label-${score}`);
      label.innerHTML = passwordStrengthMessage[score];
    }

    event.target.setAttribute('data-score', score);
  });
};
