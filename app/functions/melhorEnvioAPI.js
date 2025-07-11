const url = "https://www.melhorenvio.com.br/api/v2/me/shipment/calculate";

const token =
  "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIxIiwianRpIjoiOTRiOGIxOGM4MDlhMDVlMGM1MzdmODUxZDk2MDA1ZjhkYzNkZjQxY2JjZDRjMzI2YjIzOGExZjc5MDE3YjI2M2JiNjk3NGRiNzkxNzMyODIiLCJpYXQiOjE3NTA1MjQ1ODYuNTg0MDc3LCJuYmYiOjE3NTA1MjQ1ODYuNTg0MDc4LCJleHAiOjE3ODIwNjA1ODYuNTczNjY0LCJzdWIiOiI5ZWU5NDAyMy1mZjM0LTQ2ZWQtOTJlMy1lZjdlOTlmMTM0ODkiLCJzY29wZXMiOlsic2hpcHBpbmctY2FsY3VsYXRlIl19.aZJPsKbqItxAUFazOBfDoxqAe2VvyrzEuykIW_xCncXrvtlUALnlQWmJziqb2KKm15DNbrGPxwVsTIlEOD32MaQjKOCmUm-zKF_VawljFNoFbeZ1mGLEkiiWsVMVVkqYsMEu2Ffo6-bv1fbH-6ApSqCQvE70K2gAGT0OYa2Gvk3kYv8az96cGyg3MltjDsDjamqKY0nTXoAkoOobT5BQMqHaG4N9Y4xFlGVgNG_mFqWugNL6Im1EpwiHF_PqzOzK6lK6CZHZsdc6186xvs7Pi4GWmij4bDnbiMqZdisKavuttYpu4-KnMgI19TWei5P8mS_0LbbhSEsmfqOfqPbJklQsPtU4pc-NMsj7B-0LljdDqxPFmcDxAvvyJrbErg5UPdPutEmOknAUKZtvb2SOtccnF5eJ1fdbFzvp62JCBq8ZDxzY9lNUNYLIyJ5cu37eyz_03qVZSHkuuq_LarKZOv-TLfWO8j5-5Omk_Ma-9sqjibQkgHW8VyaxkBIB-8dTYSl-eDUKs54gAMGLTxlUjnjDf4q-ycg0NKe3fGHF4MukvhptDQsTYMB3nnTiMdRjvtfv04hgiR0S-Z28nn2LBbjsCXGJocP-248NIsPiEvOWUFi750wkmXA_xSENCIV3myuIEnyebf3wScI8NpAmHgG8INNtZrIja6qfFn7pfv0";

export const melhorEnvioAPI = async (shippingData) => {
  try {
    let res = await fetch(url, {
      headers: {
        Accept: "application/json",
        Authorization: `Bearer ${token}`,
        "Content-Type": "application/json",
        "User-Agent": "Aplicação heric.mendez00@gmail.com"
      },
      body: JSON.stringify(shippingData),
      method: 'POST'
    });
    console.log(res);
  } catch (err) {
    console.log(err);
  }
};

const shippingData = {
  "from": {
    "postal_code": "01002001"
  },
  "to": {
    "postal_code": "90570020"
  },
  "package": {
    "height": 4,
    "width": 12,
    "length": 17,
    "weight": 0.3
  }
}

melhorEnvioAPI(shippingData)