import * as React from 'react';
import classNames from 'classnames';

export default ({ progress, label, alert }: { progress: number, label: string, alert: boolean }) => (<div className="acms-admin-loading-bar acms-admin-active" >
  <div className={classNames('acms-admin-loading-bar-inner', {
    '-alert': alert
  })} style={{ width: `${progress}%` }}>
  </div>
</div>);
