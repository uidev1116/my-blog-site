import classNames from 'classnames';

interface ProgressBarProps {
  progress: number;
  alert: boolean;
}

const ProgressBar = ({ progress, alert }: ProgressBarProps) => (
  <div className="acms-admin-loading-bar acms-admin-active">
    <div
      className={classNames('acms-admin-loading-bar-inner', {
        '-alert': alert,
      })}
      style={{ width: `${progress}%` }}
    />
  </div>
);

export default ProgressBar;
