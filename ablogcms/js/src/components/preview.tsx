import React, { Component } from 'react';
import ReactDeviceMode from 'react-device-mode';
import CopyToClipboard from 'react-copy-to-clipboard';
import dayjs from 'dayjs';
import html2canvas from 'html2canvas';
import { saveAs } from 'file-saver';
import DatePicker from './date-picker';
import TimePicker from './time-picker';
import Modal from './modal';
import Spinner from './spinner';
import Notify from './notify';
import Splash from './splash';

type PreviewState = {
  date: string;
  time: string;
  rule: number;
  token: string;
  src: string;
  copied: boolean;
  isLoading: boolean;
  capturing: boolean;
  isModalOpened: boolean;
  isNaked: boolean;
  isMessageModalOpened: boolean;
  urlShareStatus: 'standby' | 'waiting' | 'done';
  url: string;
  ua: string;
};

type PreviewProps = {
  hasCloseBtn?: boolean;
  hasShareBtn?: boolean;
  defaultDevice?: string;
  timemachine: boolean;
  ruleList: { id: string; label: string }[];
  onClose: () => void;
};

export default class Preview extends Component<PreviewProps, PreviewState> {
  shareInput: HTMLInputElement | null;

  iframe: HTMLIFrameElement;

  firstUrl: string;

  static defaultProps = {
    hasCloseBtn: true,
    hasShareBtn: true,
    defaultDevice: '',
  };

  constructor(props) {
    super(props);
    const now = dayjs();
    const isNaked = !props.hasShareBtn && window.innerWidth < 768;

    this.firstUrl = props.src || location.href;
    this.state = {
      date: now.format('YYYY-MM-DD'),
      time: now.format('HH:mm:ss'),
      rule: 0,
      token: window.csrfToken,
      src: '',
      copied: false,
      isLoading: false,
      isModalOpened: false,
      capturing: false,
      isNaked,
      isMessageModalOpened: isNaked,
      urlShareStatus: 'standby', // standby | waiting | done,
      url: '',
      ua: '',
    };
  }

  componentDidUpdate() {
    const { urlShareStatus } = this.state;
    if (urlShareStatus === 'done') {
      this.shareInput?.focus();
      this.shareInput?.select();
    }
  }

  changeDate(date) {
    if (date) {
      this.setState({ date });
    }
  }

  changeTime(time) {
    if (time) {
      this.setState({ time });
    }
  }

  changeRule(event) {
    if (event.target.value) {
      this.setState({ rule: event.target.value });
    }
  }

  reload() {
    const { src } = this.iframe;
    this.iframe.src = 'about:blank';
    setTimeout(() => {
      this.iframe.src = src;
      this.iframe.onload = () => {
        this.iframe.onload = () => {}; // eslint-disable-line @typescript-eslint/no-empty-function
        this.setState({ isLoading: false });
      };
    }, 10);
  }

  close() {
    const { timemachine, onClose } = this.props;
    const fd = new FormData();

    if (timemachine) {
      fd.append('ACMS_POST_Timemachine_Disable', 'true');
      fd.append('formToken', window.csrfToken);
    } else {
      fd.append('ACMS_POST_Preview_Disable', 'true');
      fd.append('formToken', window.csrfToken);
    }
    $.ajax({
      url: ACMS.Library.acmsLink({
        bid: ACMS.Config.bid,
      }),
      type: 'POST',
      data: fd,
      processData: false,
      contentType: false,
    }).then(() => {
      if (timemachine) {
        location.reload();
      }
    });
    if (!timemachine) {
      onClose();
    }
  }

  changeContents() {
    const fd = new FormData();
    const { timemachine } = this.props;
    const {
      date, time, token, ua, rule,
    } = this.state;

    this.setState({ isLoading: true });

    if (timemachine) {
      fd.append('date', date);
      fd.append('time', time);
      fd.append('rule', `${rule}`);
      fd.append('preview_fake_ua', ua);
      fd.append('preview_token', token);
      fd.append('ACMS_POST_Timemachine_Enable', 'true');
      fd.append('formToken', window.csrfToken);
    } else {
      fd.append('preview_fake_ua', ua);
      fd.append('preview_token', token);
      fd.append('ACMS_POST_Preview_Mode', 'true');
      fd.append('formToken', window.csrfToken);
    }
    $.ajax({
      url: ACMS.Library.acmsLink({
        bid: ACMS.Config.bid,
      }),
      type: 'POST',
      data: fd,
      processData: false,
      contentType: false,
    }).then(() => {
      if (this.props.ready) {
        this.props.ready();
      }
      if (!this.state.src) {
        this.setState({
          src: this.firstUrl,
        });
      } else {
        this.reload();
      }
    });
  }

  rewriteUrl(uri) {
    if (!uri) {
      return uri;
    }
    const { token } = this.state;
    const key = 'acms-preview-mode';
    const regex = new RegExp(`([?&])${key}=.*?(&|$)`, 'i');
    const separator = uri.indexOf('?') !== -1 ? '&' : '?';
    let hash = '';
    if (uri.match(/#(.*)$/)) {
      uri = uri.replace(/#(.*)$/, (val1) => {
        hash = val1;
        return '';
      });
    }
    if (uri.match(regex)) {
      return uri.replace(regex, `$1${key}=${token}$2`);
    }
    return `${uri + separator + key}=${token}&timestamp=${new Date().getTime()}${hash}`;
  }

  openShareModal() {
    this.setState({
      isModalOpened: true,
    });
  }

  async captureHtml() {
    const target = this.iframe.contentDocument?.querySelector('body');
    if (!target) {
      return;
    }
    this.setState({
      capturing: true,
    });
    const canvas = await html2canvas(target, {
      // allowTaint: true,
      logging: false,
      width: this.iframe.offsetWidth,
    });
    canvas.toBlob((blob) => {
      if (!blob) {
        return;
      }
      saveAs(blob, `${(window.location.host + window.location.pathname).replace(/(\/|\.)/g, '_')}.png`);
      this.setState({
        capturing: false,
      });
    });
  }

  hideShareModal() {
    this.setState({
      isModalOpened: false,
      urlShareStatus: 'standby',
    });
  }

  hideMessageModal() {
    this.setState({
      isMessageModalOpened: false,
    });
  }

  getShareUrl() {
    const { src } = this.state;
    const fd = new FormData();

    this.setState({
      urlShareStatus: 'waiting',
    });
    fd.append('uri', src);
    fd.append('ACMS_POST_Preview_Share', 'true');
    fd.append('formToken', window.csrfToken);

    $.ajax({
      url: ACMS.Library.acmsLink({
        bid: ACMS.Config.bid,
      }),
      type: 'post',
      data: fd,
      processData: false,
      contentType: false,
      dataType: 'json',
    }).done((json) => {
      if (json.status === true) {
        this.setState({
          urlShareStatus: 'done',
          url: json.uri,
        });
      }
    });
  }

  render() {
    const {
      timemachine, hasShareBtn, defaultDevice, ruleList,
    } = this.props;
    const {
      date,
      time,
      rule,
      src,
      isModalOpened,
      urlShareStatus,
      url,
      copied,
      isNaked,
      isMessageModalOpened,
      capturing,
    } = this.state;
    let header: JSX.Element | null = null;
    const sub: JSX.Element[] = [];
    sub.push(
      <div style={{ display: 'inline-block', marginLeft: '25px' }}>
        <div
          onClick={this.captureHtml.bind(this)}
          onKeyDown={this.captureHtml.bind(this)}
          style={{ color: '#666', fontSize: '18px', cursor: 'pointer' }}
          role="button"
          tabIndex={0}
        >
          <span className="acms-admin-icon-config_entry_photo" />
        </div>
      </div>,
    );
    if (timemachine) {
      header = (
        <div style={{ marginBottom: '5px', display: 'flex', alignItems: 'center' }}>
          <DatePicker
            value={date}
            onChange={this.changeDate.bind(this)}
            style={{ marginRight: '5px' }}
          />
          <TimePicker
            value={time}
            onInput={this.changeTime.bind(this)}
            onChange={this.changeTime.bind(this)}
            style={{ marginRight: '5px' }}
          />
          <select value={rule} onChange={this.changeRule.bind(this)} style={{ marginRight: '5px' }}>
            <option>ルールなし</option>
            {ruleList.map((rule) => (
              <option value={rule.id}>{rule.label}</option>
            ))}
          </select>
          <button
            type="button"
            className="acms-admin-btn-admin"
            onClick={this.changeContents.bind(this)}
            style={{ minWidth: '50px' }}
          >
            {ACMS.i18n('preview.change')}
          </button>
        </div>
      );
    } else if (hasShareBtn) {
      sub.push(
        <div style={{ display: 'inline-block', marginLeft: '25px' }}>
          <div
            onClick={this.openShareModal.bind(this)}
            style={{ color: '#666', fontSize: '18px', cursor: 'pointer' }}
            onKeyDown={this.openShareModal.bind(this)}
            role="button"
            tabIndex={0}
          >
            <span className="acms-admin-icon-config_export" />
          </div>
        </div>,
      );
    }
    return (
      <>
        <div
          style={{
            position: 'absolute',
            top: 0,
            left: 0,
            width: '100%',
            height: '100%',
            zIndex: 0,
          }}
        >
          <ReactDeviceMode
            isNaked={isNaked}
            src={this.rewriteUrl(src)}
            i18n={{ fitWindow: ACMS.i18n('preview.fit_to_screen') }}
            header={header}
            sub={sub}
            onClose={() => {
              this.close();
            }}
            devices={ACMS.Config.previewDevices}
            defaultDevice={defaultDevice}
            getIframe={(iframe) => {
              this.iframe = iframe;
            }}
            onDeviceUpdated={(device) => {
              this.setState(
                {
                  ua: device.ua,
                  isLoading: true,
                },
                () => {
                  this.changeContents();
                },
              );
            }}
            onUrlChange={(nextUrl) => {
              if (nextUrl && nextUrl !== 'about:blank') {
                let isLoading = true;
                if (/(&|\?)acms-preview-mode=/.test(nextUrl)) {
                  isLoading = false;
                }
                this.setState(
                  {
                    src: nextUrl,
                    isLoading,
                  },
                  () => {
                    this.forceUpdate();
                  },
                );
              } else {
                this.setState(
                  {
                    isLoading: false,
                  },
                  () => {
                    this.forceUpdate();
                  },
                );
              }
            }}
            hasCloseBtn={this.props.hasCloseBtn}
            isLoading={this.state.isLoading}
          />
        </div>
        <Modal
          isOpen={isMessageModalOpened}
          title={<h3>{ACMS.i18n('preview.preview_mode')}</h3>}
          onClose={this.hideMessageModal.bind(this)}
          dialogStyle={{ maxWidth: '600px', marginTop: '100px' }}
          style={{ backgroundColor: 'rgba(0, 0, 0, .5)' }}
        >
          <p>{ACMS.i18n('preview.confirm_txt')}</p>
        </Modal>

        <Modal
          isOpen={isModalOpened}
          title={<h3>{ACMS.i18n('preview.share')}</h3>}
          onClose={this.hideShareModal.bind(this)}
          dialogStyle={{ maxWidth: '600px', marginTop: '100px' }}
          style={{ backgroundColor: 'rgba(0, 0, 0, .5)' }}
        >
          <div>
            {urlShareStatus === 'standby' && (
              <p>
                <button
                  type="button"
                  className="acms-admin-btn acms-admin-btn-large acms-admin-btn-flat-primary acms-admin-btn-block"
                  onClick={this.getShareUrl.bind(this)}
                >
                  {' '}
                  <span className="acms-admin-icon acms-admin-icon-config_links" />
                  {' '}
                  {ACMS.i18n('preview.get_link')}
                </button>
              </p>
            )}
            {urlShareStatus === 'waiting' && (
              <div style={{ position: 'relative', height: '50px' }}>
                <Spinner size={20} />
              </div>
            )}
            {urlShareStatus === 'done' && (
              <div className="acms-admin-form-group">
                <p className="acms-admin-form">
                  <div className="acms-admin-form-action">
                    <input
                      type="email"
                      className="acms-admin-form-width-full"
                      value={url}
                      ref={(shareInput) => {
                        this.shareInput = shareInput;
                      }}
                    />
                    <span className="acms-admin-form-side-btn">
                      <CopyToClipboard
                        text={url}
                        onCopy={() => {
                          this.setState({
                            copied: true,
                          });
                        }}
                      >
                        <button type="button" className="acms-admin-btn">
                          {ACMS.i18n('preview.copy')}
                        </button>
                      </CopyToClipboard>
                    </span>
                  </div>
                </p>
              </div>
            )}
            <p>{ACMS.i18n('preview.get_link_detail')}</p>
            <p>
              （
              {ACMS.i18n('preview.expiration')}
              :
              {ACMS.Config.urlPreviewExpire}
              {' '}
              {ACMS.i18n('preview.hours')}
              ）
            </p>
          </div>
        </Modal>
        <Notify
          message={ACMS.i18n('preview.copy_to_clipboard')}
          show={copied}
          onFinish={() => {
            this.setState({ copied: false });
          }}
        />
        {capturing && <Splash message={ACMS.i18n('preview.capturing')} />}
      </>
    );
  }
}
