type PreviewShareResponse =
  | {
      status: true;
      uri: string;
    }
  | {
      status: false;
      message: string;
    };

export async function enablePreviewMode(formData: FormData) {
  formData.append('ACMS_POST_Preview_Mode', 'true');
  formData.append('formToken', window.csrfToken);

  const response = await fetch(ACMS.Library.acmsLink({ bid: ACMS.Config.bid }), {
    method: 'POST',
    body: formData,
  });
  if (!response.ok) {
    throw new Error('Failed to enable preview mode');
  }
}

export async function disablePreviewMode() {
  const formData = new FormData();
  formData.append('ACMS_POST_Preview_Disable', 'true');
  formData.append('formToken', window.csrfToken);
  const response = await fetch(ACMS.Library.acmsLink({ bid: ACMS.Config.bid }), {
    method: 'POST',
    body: formData,
  });
  if (!response.ok) {
    throw new Error('Failed to disable preview mode');
  }
}

export async function createPreviewShareUrl(formData: FormData) {
  formData.append('ACMS_POST_Preview_Share', 'true');
  formData.append('formToken', window.csrfToken);
  const response = await fetch(ACMS.Library.acmsLink({ bid: ACMS.Config.bid }), {
    method: 'POST',
    body: formData,
  });
  if (!response.ok) {
    throw new Error('Failed to create preview share URL');
  }

  const data: PreviewShareResponse = await response.json();
  if (!data.status) {
    throw new Error(data.message);
  }

  return data.uri;
}
