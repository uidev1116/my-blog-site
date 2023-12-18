import React, { useState, useEffect, useRef } from 'react'
import { render } from 'react-dom'
import ContentLoader from 'react-content-loader'
import ReactTooltip from 'react-tooltip'
import Highlight from 'react-highlight'
import { CopyToClipboard } from 'react-copy-to-clipboard'
import axios from 'axios'
import nl2br from 'react-nl2br'
import type { AxiosRequestConfig, AxiosResponse } from 'axios'
import 'react-highlight/node_modules/highlight.js/styles/atom-one-dark.css'
import Modal from './modal'
import { parseQuery } from '../utils'

interface Log {
  id: number
  success: boolean
  datetime: string
  level: string
  levelName: string
  suid: number
  sessionUserName: string
  sessionUserMail: string
  bid: number
  blogName: string
  message: string
  url: string
  ua: string
  referer: string
  ipAddress: string
  method: string
  httpStatus: string
  responseTime: number
  eid: null | number
  cid: null | number
  uid: null | number
  rid: null | number
  ruleName: string
  extra: string
  context: string
  switchUserName: string
  switchUserMail: string
  reqHeader: string
  reqBody: string
  acmsPost: string
}

const TablePlaceholder = () => (
  <ContentLoader viewBox="0 0 400 190" speed={0.9}>
    <rect x="0" y="0" rx="1" ry="1" width="50" height="8" />
    <rect x="55" y="0" rx="1" ry="1" width="345" height="8" />
    <rect x="0" y="16" rx="1" ry="1" width="50" height="8" />
    <rect x="55" y="16" rx="1" ry="1" width="270" height="8" />
    <rect x="0" y="32" rx="1" ry="1" width="50" height="8" />
    <rect x="55" y="32" rx="1" ry="1" width="230" height="8" />
    <rect x="0" y="48" rx="1" ry="1" width="50" height="8" />
    <rect x="55" y="48" rx="1" ry="1" width="345" height="8" />
    <rect x="0" y="64" rx="1" ry="1" width="50" height="8" />
    <rect x="55" y="64" rx="1" ry="1" width="140" height="8" />
    <rect x="0" y="80" rx="1" ry="1" width="50" height="8" />
    <rect x="55" y="80" rx="1" ry="1" width="200" height="8" />
    <rect x="0" y="96" rx="1" ry="1" width="50" height="8" />
    <rect x="55" y="96" rx="1" ry="1" width="345" height="8" />
    <rect x="0" y="112" rx="1" ry="1" width="50" height="8" />
    <rect x="55" y="112" rx="1" ry="1" width="270" height="8" />
    <rect x="0" y="128" rx="1" ry="1" width="50" height="8" />
    <rect x="55" y="128" rx="1" ry="1" width="230" height="8" />
    <rect x="0" y="144" rx="1" ry="1" width="50" height="8" />
    <rect x="55" y="144" rx="1" ry="1" width="345" height="8" />
    <rect x="0" y="160" rx="1" ry="1" width="50" height="8" />
    <rect x="55" y="160" rx="1" ry="1" width="140" height="8" />
    <rect x="0" y="176" rx="1" ry="1" width="50" height="8" />
    <rect x="55" y="176" rx="1" ry="1" width="200" height="8" />
  </ContentLoader>
)

const DetailLogModal = () => {
  const [isOpen, setIsOpen] = useState<boolean>(false)
  const [isReady, setIsReady] = useState<boolean>(false)
  const [logInfo, setLogInfo] = useState<Log>({} as Log)
  const [errorMessage, setErrorMessage] = useState<string>('')
  const copyButtonToolTip = useRef<HTMLParagraphElement>(null)

  const fetchLogInfo = async (id: string) => {
    setLogInfo({} as Log)
    setIsReady(false)
    setIsOpen(true)

    const params = new URLSearchParams()
    params.append('ACMS_POST_Logger_Info', 'post')
    params.append('id', id)
    params.append('formToken', window.csrfToken)

    const axiosOptions: AxiosRequestConfig = {
      method: 'POST',
      url: window.location.href,
      responseType: 'json',
      data: params,
    }

    try {
      const response: AxiosResponse<Log> = await axios(axiosOptions)
      const json = response.data
      if (response.status === 200) {
        if (json && json.success === true) {
          setLogInfo(json)
          setIsReady(true)

          if ('history' in window) {
            history.replaceState(null, '', `#id=${json.id}`)
          }
        }
      } else {
        setErrorMessage('ログの取得に失敗しました')
      }
    } catch (e) {
      if (axios.isAxiosError(e) && e.response && e.response.status === 400) {
        setErrorMessage(`ログの取得に失敗しました。${e.message}`)
      }
    }
  }

  useEffect(() => {
    const detailBtns = document.querySelectorAll('.js-logger-show-detail')

    const handleClick = (e: MouseEvent) => {
      e.preventDefault()
      if (!(e.currentTarget instanceof HTMLButtonElement)) {
        return
      }
      const id = e.currentTarget.getAttribute('data-id')
      if (id) {
        fetchLogInfo(id)
      }
    }

    ;[].forEach.call(detailBtns, (button: HTMLButtonElement) => {
      button.addEventListener('click', handleClick)
    })

    if (window.location.hash) {
      const { id } = parseQuery(window.location.hash.slice(1))
      if (id) {
        fetchLogInfo(id)
      }
    }

    return () => {
      ;[].forEach.call(detailBtns, (button: HTMLButtonElement) => {
        button.removeEventListener('click', handleClick)
      })
    }
  }, [])

  const ModalContent = (
    <div>
      <div
        style={{
          display: 'flex',
          justifyContent: 'flex-end',
          marginTop: '15px',
        }}
      >
        <p
          ref={copyButtonToolTip}
          data-tip={ACMS.i18n('logger.modal.clipboardMessage')}
          data-place="left"
          data-type="dark"
          data-effect="solid"
        />
        <ReactTooltip />
        <CopyToClipboard
          text={JSON.stringify(logInfo, null, 2)}
          onCopy={() => {
            setTimeout(
              () =>
                copyButtonToolTip.current &&
                ReactTooltip.hide(copyButtonToolTip.current),
              2000,
            )
          }}
        >
          <button
            type="button"
            className="acms-admin-btn-admin"
            onClick={() =>
              copyButtonToolTip.current &&
              ReactTooltip.show(copyButtonToolTip.current)
            }
          >
            {ACMS.i18n('logger.modal.clipboardButton')}
          </button>
        </CopyToClipboard>
      </div>
      <div
        className="acms-admin-log-table"
        style={{ margin: '15px 0', overflowX: 'scroll' }}
      >
        {errorMessage && (
          <p className="acms-admin-alert acms-admin-alert-danger">
            {errorMessage}
          </p>
        )}

        {!isReady && <TablePlaceholder />}
        {isReady && (
          <table className="acms-admin-table acms-admin-table-striped">
            <tbody>
              <tr>
                <th className="acms-admin-table-nowrap">
                  {ACMS.i18n('logger.modal.datetime')}
                </th>
                <td>{logInfo.datetime}</td>
              </tr>
              <tr>
                <th className="acms-admin-table-nowrap">
                  {ACMS.i18n('logger.modal.level')}
                </th>
                <td>{logInfo.levelName}</td>
              </tr>
              <tr>
                <th className="acms-admin-table-nowrap">
                  {ACMS.i18n('logger.modal.message')}
                </th>
                <td>{nl2br(logInfo.message)}</td>
              </tr>
              <tr>
                <th className="acms-admin-table-nowrap">
                  {ACMS.i18n('logger.modal.blog')}
                </th>
                <td>
                  {logInfo.blogName && (
                    <>
                      {logInfo.blogName}
                      &nbsp; &#40;id=
                      {logInfo.bid}
                      &#41;
                    </>
                  )}
                </td>
              </tr>
              <tr>
                <th className="acms-admin-table-nowrap">
                  {ACMS.i18n('logger.modal.loginUserName')}
                </th>
                <td>
                  {logInfo.sessionUserName && (
                    <>
                      {logInfo.sessionUserName}
                      &nbsp; &#40;
                      {logInfo.sessionUserMail}
                      &#41;
                    </>
                  )}
                </td>
              </tr>
              {logInfo.switchUserName && (
                <tr>
                  <th className="acms-admin-table-nowrap">
                    {ACMS.i18n('logger.modal.switchUserName')}
                  </th>
                  <td>
                    {logInfo.switchUserName}
                    &nbsp; &#40;
                    {logInfo.switchUserMail}
                    &#41;
                  </td>
                </tr>
              )}
              <tr>
                <th className="acms-admin-table-nowrap">URL</th>
                <td>
                  <a href={logInfo.url} target="_blank" rel="noreferrer">
                    {logInfo.url}
                  </a>
                </td>
              </tr>
              <tr>
                <th className="acms-admin-table-nowrap">UA</th>
                <td>{logInfo.ua}</td>
              </tr>
              <tr>
                <th className="acms-admin-table-nowrap">
                  {ACMS.i18n('logger.modal.ipAddress')}
                </th>
                <td>{logInfo.ipAddress}</td>
              </tr>
              <tr>
                <th className="acms-admin-table-nowrap">
                  {ACMS.i18n('logger.modal.referer')}
                </th>
                <td>{logInfo.referer}</td>
              </tr>
              <tr>
                <th className="acms-admin-table-nowrap">
                  {ACMS.i18n('logger.modal.method')}
                </th>
                <td>{logInfo.method}</td>
              </tr>
              <tr>
                <th className="acms-admin-table-nowrap">
                  {ACMS.i18n('logger.modal.httpStatus')}
                </th>
                <td>{logInfo.httpStatus}</td>
              </tr>
              <tr>
                <th className="acms-admin-table-nowrap">
                  {ACMS.i18n('logger.modal.responseTime')}
                </th>
                <td>
                  {logInfo.responseTime}
                  &nbsp; ms
                </td>
              </tr>
              <tr>
                <th className="acms-admin-table-nowrap">
                  {ACMS.i18n('logger.modal.ruleName')}
                </th>
                <td>
                  {logInfo.rid && (
                    <>
                      {logInfo.ruleName}
                      &nbsp; &#40;id=
                      {logInfo.rid}
                      &#41;
                    </>
                  )}
                </td>
              </tr>
              {logInfo.acmsPost && (
                <tr>
                  <th className="acms-admin-table-nowrap">
                    {ACMS.i18n('logger.modal.postModule')}
                  </th>
                  <td>{logInfo.acmsPost}</td>
                </tr>
              )}
              {logInfo.reqHeader && (
                <tr>
                  <th className="acms-admin-table-nowrap">
                    {ACMS.i18n('logger.modal.httpRequestHeader')}
                  </th>
                  <td>
                    <Highlight className="json">
                      {JSON.stringify(logInfo.reqHeader, null, 2)}
                    </Highlight>
                  </td>
                </tr>
              )}
              {logInfo.reqBody && (
                <tr>
                  <th className="acms-admin-table-nowrap">
                    {ACMS.i18n('logger.modal.httpRequestBody')}
                  </th>
                  <td>
                    <Highlight className="json">
                      {JSON.stringify(logInfo.reqBody, null, 2)}
                    </Highlight>
                  </td>
                </tr>
              )}
              {logInfo.context && (
                <tr>
                  <th className="acms-admin-table-nowrap">
                    {ACMS.i18n('logger.modal.extraInfo')}
                  </th>
                  <td>
                    <Highlight className="json">
                      {JSON.stringify(logInfo.context, null, 2)}
                    </Highlight>
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        )}
      </div>
    </div>
  )

  return (
    <Modal
      isOpen={isOpen}
      onClose={() => {
        setIsOpen(false)
        window.location.hash = ''
      }}
      title={<h3>{ACMS.i18n('logger.modal.title')}</h3>}
      dialogStyle={{ maxWidth: '1200px', marginTop: '20px' }}
      style={{ backgroundColor: 'rgba(0, 0, 0, .5)' }}
      className="acms-admin-logger-modal"
    >
      {ModalContent}
    </Modal>
  )
}
export default (target: HTMLElement) => {
  render(<DetailLogModal />, target)
}
