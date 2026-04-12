import{j as e}from"./vendor-mui-2cHuhLvv.js";import{g as I,l as k,r as z}from"./vendor-react-D-Ndkw3L.js";import{u as E,L as _}from"./index-7-EqB6PR.js";import{S as O}from"./Sidebar-eX4EUtAu.js";import{u as L}from"./vendor-i18n-Dzw_n1Df.js";import"./addressStore-BBJmdITp.js";import"./profileStore-PqeK76mz.js";import{a as V}from"./ordersStore-DQ2lNof2.js";import{r as F}from"./vendor-html2pdf-DS46S6o7.js";import"./vendor-zustand-BLdIKjHb.js";import"./vendor-axios-C7aFLusC.js";import"./vendor-toast-CM0JZaQ8.js";import"./vendor-swiper-adhIqUnC.js";function q({title:m,points:o,btn:v,onPrint:g,onShare:f}){const{t:h}=L(),a=()=>{g?g():window.print()};return e.jsx("div",{children:e.jsxs("div",{className:"w-full min-h-[45px] p-4 my-5 bg-[#E5E5E5] flex flex-col sm:flex-row items-center justify-between gap-3 rounded-[8px]",children:[e.jsx("p",{className:"text-[#211C4D] text-[20px] sm:text-[24px] font-[500]",children:m}),e.jsx("p",{className:"text-[#211C4DCC] text-[14px] sm:text-[16px] font-[500]",children:o}),v?e.jsxs("div",{className:"flex items-center gap-2",children:[e.jsx("button",{className:"w-[129px] h-[35px] bg-[#211C4D] rounded-[8px] text-white text-[14px] sm:text-[16px] whitespace-nowrap",onClick:a,children:h("PrintInvoice",{defaultValue:"طباعه الفاتوره"})}),f&&e.jsxs("button",{className:"h-[35px] px-3 bg-[#211C4D] rounded-[8px] text-white text-[14px] sm:text-[16px] whitespace-nowrap flex items-center gap-1.5",onClick:f,title:h("ShareInvoice",{defaultValue:"مشاركة الفاتورة"}),children:[e.jsxs("svg",{xmlns:"http://www.w3.org/2000/svg",width:"18",height:"18",viewBox:"0 0 24 24",fill:"none",stroke:"currentColor",strokeWidth:"2",strokeLinecap:"round",strokeLinejoin:"round",children:[e.jsx("circle",{cx:"18",cy:"5",r:"3"}),e.jsx("circle",{cx:"6",cy:"12",r:"3"}),e.jsx("circle",{cx:"18",cy:"19",r:"3"}),e.jsx("line",{x1:"8.59",y1:"13.51",x2:"15.42",y2:"17.49"}),e.jsx("line",{x1:"15.41",y1:"6.51",x2:"8.59",y2:"10.49"})]}),h("Share",{defaultValue:"مشاركة"})]})]}):null]})})}var U=F();const D=I(U);function ot(){const{id:m}=k(),{currentInvoice:o,singleLoading:v,singleError:g,fetchInvoiceById:f}=V(),h=z.useRef(null),{lang:a="ar"}=E();L();const t=a==="ar";z.useEffect(()=>{m&&f(parseInt(m))},[m,f]);const A=()=>{if(!o?.order)return{subtotal:0,tax:0,total:0,discount:0,vipDiscount:0,vipTierLabel:""};const i=o.order.items.reduce((n,y)=>n+y.total,0),l=.15,r=i*l/(1+l),N=i-r,s=o.order.discount||0,x=o.order.vip_discount||0,u=o.order.vip_tier_label||"",$=o.order.shipping||0,w=i+$-s-x;return{subtotal:N,tax:r,total:w,discount:s,vipDiscount:x,vipTierLabel:u}},{subtotal:j,tax:S,total:b,discount:d,vipDiscount:p,vipTierLabel:c}=A(),R=()=>{if(!o)return;const i=window.open("","_blank");if(!i){alert("Please allow popups to print the invoice.");return}const l=`
        <!DOCTYPE html>
        <html lang="${a}" dir="${t?"rtl":"ltr"}">
        <head>
            <meta charset="UTF-8">
            <title>${t?"فاتورة":"Invoice"} #${o.invoice_number}</title>
            <style>
                body { font-family: 'Cairo', 'Arial', sans-serif; margin: 0; padding: 0; background: #f3f4f6; color: black; direction: ${t?"rtl":"ltr"}; }
                * { box-sizing: border-box; }
                @media print {
                    body { background: white; }
                    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                }
            </style>
        </head>
        <body dir="${t?"rtl":"ltr"}">
            <div style="max-width: 800px; margin: 40px auto; background: white; padding: 40px; direction: ${t?"rtl":"ltr"};">

                <!-- Header -->
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; padding-bottom: 20px; border-bottom: 4px solid #2c3e50;">
                    <div style="color: #2c3e50; text-align: ${t?"right":"left"};">
                        <h1 style="font-size: 28px; font-weight: bold; margin-bottom: 8px; margin-top: 0;">City Phones</h1>
                        <p style="font-size: 14px; color: #7f8c8d; margin: 0;">${t?"المملكة العربية السعودية":"Kingdom of Saudi Arabia"}</p>
                    </div>
                    <div style="text-align: ${t?"left":"right"};">
                        <h2 style="color: #211C4D; font-size: 32px; font-weight: bold; margin-bottom: 8px; margin-top: 0;">${t?"فاتورة":"INVOICE"}</h2>
                        <p style="font-size: 14px; margin: 4px 0; color: #555;"><strong>${t?"رقم الفاتورة:":"Invoice No:"}</strong> <span style="color: #2c3e50;">${o.invoice_number}</span></p>
                        <p style="font-size: 14px; margin: 4px 0; color: #555;"><strong>${t?"تاريخ الفاتورة:":"Date:"}</strong> <span style="color: #2c3e50;">${o.invoice_date}</span></p>
                        <p style="font-size: 14px; margin: 4px 0; color: #555;"><strong>${t?"رقم الطلب:":"Order No:"}</strong> <span style="color: #2c3e50;">${o.order_number||"-"}</span></p>
                    </div>
                </div>

                <!-- Details -->
                <div style="display: flex; justify-content: space-between; margin-bottom: 40px; gap: 20px; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 200px;">
                        <h3 style="color: #2c3e50; font-size: 16px; font-weight: bold; margin-bottom: 12px; border-bottom: 1px solid #eee; padding-bottom: 4px; margin-top: 0;">${t?"معلومات الطلب":"Order Info"}</h3>
                        <p style="font-size: 14px; color: #555; margin: 4px 0;"><strong>${t?"الحالة:":"Status:"}</strong> ${t?"مكتمل":"Completed"}</p>
                        <p style="font-size: 14px; color: #555; margin: 4px 0;"><strong>${t?"طريقة الدفع:":"Payment:"}</strong> ${t?o.order.payment_method?.name_ar||"-":o.order.payment_method?.name_en||o.order.payment_method?.name_ar||"-"}</p>
                    </div>
                    <div style="flex: 1; min-width: 200px;">
                        <h3 style="color: #2c3e50; font-size: 16px; font-weight: bold; margin-bottom: 12px; border-bottom: 1px solid #eee; padding-bottom: 4px; margin-top: 0;">${t?"العميل":"Customer"}</h3>
                        <p style="font-size: 14px; color: #555; margin: 4px 0;"><strong>${t?"الاسم:":"Name:"}</strong> ${o.order.location?.first_name||""} ${o.order.location?.last_name||(t?"عميل":"Customer")}</p>
                    </div>
                </div>

                <!-- Items Table -->
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 32px; direction: ${t?"rtl":"ltr"};">
                    <thead>
                        <tr style="background-color: #211C4D; color: white;">
                            <th style="padding: 12px; text-align: ${t?"right":"left"}; font-size: 14px;">${t?"المنتج":"Product"}</th>
                            <th style="padding: 12px; text-align: ${t?"right":"left"}; font-size: 14px;">${t?"الكمية":"Qty"}</th>
                            <th style="padding: 12px; text-align: ${t?"right":"left"}; font-size: 14px;">${t?"السعر":"Price"}</th>
                            <th style="padding: 12px; text-align: ${t?"right":"left"}; font-size: 14px;">${t?"الإجمالي":"Total"}</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${o.order.items.map(r=>`
                            <tr style="border-bottom: 1px solid #e5e7eb;">
                                <td style="padding: 12px; font-size: 14px; color: #333; text-align: ${t?"right":"left"};">
                                    <strong>${a==="ar"?r.product.name_ar||r.product.name:r.product.name_en||r.product.name}</strong>
                                    ${r.product_option?`<br/><small style="color: #9ca3af;">${r.product_option.value}</small>`:""}
                                </td>
                                <td style="padding: 12px; font-size: 14px; color: #333; text-align: ${t?"right":"left"};">${r.quantity}</td>
                                <td style="padding: 12px; font-size: 14px; color: #333; text-align: ${t?"right":"left"};">${r.price.toLocaleString()} ${t?"رس":"SAR"}</td>
                                <td style="padding: 12px; font-size: 14px; color: #333; font-weight: bold; text-align: ${t?"right":"left"};">${r.total.toLocaleString()} ${t?"رس":"SAR"}</td>
                            </tr>
                        `).join("")}
                    </tbody>
                </table>

                <!-- Totals -->
                <div style="display: flex; justify-content: ${t?"flex-start":"flex-end"}; margin-bottom: 40px;">
                    <table style="width: 350px; direction: ${t?"rtl":"ltr"};">
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 8px; font-size: 14px; color: #7f8c8d; text-align: ${t?"right":"left"};">${t?"المجموع الفرعي":"Subtotal"}</td>
                            <td style="padding: 8px; font-size: 14px; text-align: ${t?"left":"right"}; font-weight: 600; color: #333;">${j.toLocaleString()} ${t?"رس":"SAR"}</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 8px; font-size: 14px; color: #7f8c8d; text-align: ${t?"right":"left"};">${t?"الضريبة":"Tax"}</td>
                            <td style="padding: 8px; font-size: 14px; text-align: ${t?"left":"right"}; font-weight: 600; color: #333;">${S.toLocaleString()} ${t?"رس":"SAR"}</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 8px; font-size: 14px; color: #7f8c8d; text-align: ${t?"right":"left"};">${t?"الشحن":"Shipping"}</td>
                            <td style="padding: 8px; font-size: 14px; text-align: ${t?"left":"right"}; font-weight: 600; color: #333;">${o.order.shipping?.toLocaleString()||0} ${t?"رس":"SAR"}</td>
                        </tr>
                        ${d>0?`<tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 8px; font-size: 14px; color: #16a34a; text-align: ${t?"right":"left"};">${t?"الخصم":"Discount"}</td>
                            <td style="padding: 8px; font-size: 14px; text-align: ${t?"left":"right"}; font-weight: 600; color: #16a34a;">-${d.toLocaleString()} ${t?"رس":"SAR"}</td>
                        </tr>`:""}
                        ${p>0?`<tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 8px; font-size: 14px; color: #2563eb; text-align: ${t?"right":"left"};">${t?"خصم VIP":"VIP Discount"}${c?` (${c})`:""}</td>
                            <td style="padding: 8px; font-size: 14px; text-align: ${t?"left":"right"}; font-weight: 600; color: #2563eb;">-${p.toLocaleString()} ${t?"رس":"SAR"}</td>
                        </tr>`:""}
                        <tr style="background-color: #2c3e50; color: white; font-size: 18px; font-weight: bold;">
                            <td style="padding: 12px; text-align: ${t?"right":"left"};">${t?"الإجمالي":"Total"}</td>
                            <td style="padding: 12px; text-align: ${t?"left":"right"};">${b.toLocaleString()} ${t?"رس":"SAR"}</td>
                        </tr>
                    </table>
                </div>

                <!-- Footer -->
                <div style="text-align: center; padding-top: 20px; border-top: 2px solid #f3f4f6; font-size: 12px; color: #7f8c8d; margin-top: 48px;">
                    <p style="margin-bottom: 4px;">${t?"شكراً لتعاملكم معنا!":"Thank you for your business!"}</p>
                    <p>${t?"هذه فاتورة إلكترونية صالحة بدون توقيع.":"This is a valid electronic invoice without signature."}</p>
                </div>
            </div>
            <script>
                window.onload = function() { window.print(); }
            <\/script>
        </body>
        </html>
    `;i.document.write(l),i.document.close()},[T,C]=z.useState(!1),P=async()=>{if(!o||T)return;C(!0);let i=null;try{const l=`${t?"فاتورة":"Invoice"} #${o.invoice_number}`,r=t?`فاتورة من City Phones - رقم ${o.invoice_number}`:`Invoice from City Phones - #${o.invoice_number}`,N=`
        <!DOCTYPE html>
        <html lang="${a}" dir="${t?"rtl":"ltr"}">
        <head>
          <meta charset="UTF-8">
          <style>
            body { margin: 0; padding: 0; background: white; color: black; font-family: 'Cairo', 'Arial', sans-serif; }
            * { box-sizing: border-box; }
          </style>
        </head>
        <body>
          <div style="direction: ${t?"rtl":"ltr"}; padding: 40px; max-width: 800px; margin: 0 auto;">
            <!-- Header -->
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; padding-bottom: 20px; border-bottom: 4px solid #2c3e50;">
              <div style="color: #2c3e50; text-align: ${t?"right":"left"}">
                <h1 style="font-size: 28px; font-weight: bold; margin-bottom: 8px; margin-top: 0;">City Phones</h1>
                <p style="font-size: 14px; color: #7f8c8d; margin: 0;">${t?"المملكة العربية السعودية":"Kingdom of Saudi Arabia"}</p>
              </div>
              <div style="text-align: ${t?"left":"right"};">
                <h2 style="color: #211C4D; font-size: 32px; font-weight: bold; margin-bottom: 8px; margin-top: 0;">${t?"فاتورة":"INVOICE"}</h2>
                <p style="font-size: 14px; margin: 4px 0; color: #555;"><strong>${t?"رقم الفاتورة:":"Invoice No:"}</strong> <span style="color: #2c3e50;">${o.invoice_number}</span></p>
                <p style="font-size: 14px; margin: 4px 0; color: #555;"><strong>${t?"تاريخ الفاتورة:":"Date:"}</strong> <span style="color: #2c3e50;">${o.invoice_date}</span></p>
                <p style="font-size: 14px; margin: 4px 0; color: #555;"><strong>${t?"رقم الطلب:":"Order No:"}</strong> <span style="color: #2c3e50;">${o.order_number||"-"}</span></p>
              </div>
            </div>

            <!-- Details -->
            <div style="display: flex; justify-content: space-between; margin-bottom: 40px; gap: 20px; flex-wrap: wrap;">
              <div style="flex: 1; min-width: 200px;">
                <h3 style="color: #2c3e50; font-size: 16px; font-weight: bold; margin-bottom: 12px; border-bottom: 1px solid #eee; padding-bottom: 4px; margin-top: 0;">${t?"معلومات الطلب":"Order Info"}</h3>
                <p style="font-size: 14px; color: #555; margin: 4px 0;"><strong>${t?"الحالة:":"Status:"}</strong> ${t?"مكتمل":"Completed"}</p>
                <p style="font-size: 14px; color: #555; margin: 4px 0;"><strong>${t?"طريقة الدفع:":"Payment:"}</strong> ${t?o.order.payment_method?.name_ar||"-":o.order.payment_method?.name_en||o.order.payment_method?.name_ar||"-"}</p>
              </div>
              <div style="flex: 1; min-width: 200px;">
                <h3 style="color: #2c3e50; font-size: 16px; font-weight: bold; margin-bottom: 12px; border-bottom: 1px solid #eee; padding-bottom: 4px; margin-top: 0;">${t?"العميل":"Customer"}</h3>
                <p style="font-size: 14px; color: #555; margin: 4px 0;"><strong>${t?"الاسم:":"Name:"}</strong> ${o.order.location?.first_name||""} ${o.order.location?.last_name||(t?"عميل":"Customer")}</p>
              </div>
            </div>

            <!-- Items Table -->
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 32px;">
              <thead>
                <tr style="background-color: #211C4D; color: white;">
                  <th style="padding: 12px; text-align: ${t?"right":"left"}; font-size: 14px;">${t?"المنتج":"Product"}</th>
                  <th style="padding: 12px; text-align: ${t?"right":"left"}; font-size: 14px;">${t?"الكمية":"Qty"}</th>
                  <th style="padding: 12px; text-align: ${t?"right":"left"}; font-size: 14px;">${t?"السعر":"Price"}</th>
                  <th style="padding: 12px; text-align: ${t?"right":"left"}; font-size: 14px;">${t?"الإجمالي":"Total"}</th>
                </tr>
              </thead>
              <tbody>
                ${o.order.items.map(n=>`
                  <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 12px; font-size: 14px; color: #333; text-align: ${t?"right":"left"}">
                      <strong>${a==="ar"?n.product.name_ar||n.product.name:n.product.name_en||n.product.name}</strong>
                      ${n.product_option?`<br/><small style="color: #9ca3af;">${n.product_option.value}</small>`:""}
                    </td>
                    <td style="padding: 12px; font-size: 14px; color: #333; text-align: ${t?"right":"left"}">${n.quantity}</td>
                    <td style="padding: 12px; font-size: 14px; color: #333; text-align: ${t?"right":"left"}">${n.price.toLocaleString()} ${t?"رس":"SAR"}</td>
                    <td style="padding: 12px; font-size: 14px; color: #333; font-weight: bold; text-align: ${t?"right":"left"}">${n.total.toLocaleString()} ${t?"رس":"SAR"}</td>
                  </tr>
                `).join("")}
              </tbody>
            </table>

            <!-- Totals -->
            <div style="display: flex; justify-content: ${t?"flex-start":"flex-end"}; margin-bottom: 40px;">
              <table style="width: 350px;">
                <tr style="border-bottom: 1px solid #e5e7eb;">
                  <td style="padding: 8px; font-size: 14px; color: #7f8c8d; text-align: ${t?"right":"left"};">${t?"المجموع الفرعي":"Subtotal"}</td>
                  <td style="padding: 8px; font-size: 14px; text-align: ${t?"left":"right"}; font-weight: 600; color: #333;">${j.toLocaleString()} ${t?"رس":"SAR"}</td>
                </tr>
                <tr style="border-bottom: 1px solid #e5e7eb;">
                  <td style="padding: 8px; font-size: 14px; color: #7f8c8d; text-align: ${t?"right":"left"};">${t?"الضريبة":"Tax"}</td>
                  <td style="padding: 8px; font-size: 14px; text-align: ${t?"left":"right"}; font-weight: 600; color: #333;">${S.toLocaleString()} ${t?"رس":"SAR"}</td>
                </tr>
                <tr style="border-bottom: 1px solid #e5e7eb;">
                  <td style="padding: 8px; font-size: 14px; color: #7f8c8d; text-align: ${t?"right":"left"};">${t?"الشحن":"Shipping"}</td>
                  <td style="padding: 8px; font-size: 14px; text-align: ${t?"left":"right"}; font-weight: 600; color: #333;">${o.order.shipping?.toLocaleString()||0} ${t?"رس":"SAR"}</td>
                </tr>
                ${d>0?`<tr style="border-bottom: 1px solid #e5e7eb;">
                  <td style="padding: 8px; font-size: 14px; color: #16a34a; text-align: ${t?"right":"left"};">${t?"الخصم":"Discount"}</td>
                  <td style="padding: 8px; font-size: 14px; text-align: ${t?"left":"right"}; font-weight: 600; color: #16a34a;">-${d.toLocaleString()} ${t?"رس":"SAR"}</td>
                </tr>`:""}
                ${p>0?`<tr style="border-bottom: 1px solid #e5e7eb;">
                  <td style="padding: 8px; font-size: 14px; color: #2563eb; text-align: ${t?"right":"left"};">${t?"خصم VIP":"VIP Discount"}${c?` (${c})`:""}</td>
                  <td style="padding: 8px; font-size: 14px; text-align: ${t?"left":"right"}; font-weight: 600; color: #2563eb;">-${p.toLocaleString()} ${t?"رس":"SAR"}</td>
                </tr>`:""}
                <tr style="background-color: #2c3e50; color: white; font-size: 18px; font-weight: bold;">
                  <td style="padding: 12px; text-align: ${t?"right":"left"};">${t?"الإجمالي":"Total"}</td>
                  <td style="padding: 12px; text-align: ${t?"left":"right"};">${b.toLocaleString()} ${t?"رس":"SAR"}</td>
                </tr>
              </table>
            </div>

            <!-- Footer -->
            <div style="text-align: center; padding-top: 20px; border-top: 2px solid #f3f4f6; font-size: 12px; color: #7f8c8d; margin-top: 48px;">
              <p style="margin-bottom: 4px;">${t?"شكراً لتعاملكم معنا!":"Thank you for your business!"}</p>
              <p>${t?"هذه فاتورة إلكترونية صالحة بدون توقيع.":"This is a valid electronic invoice without signature."}</p>
            </div>
          </div>
        </body>
        </html>
      `;if(typeof D!="function")throw new Error("مكتبة التصدير غير متوفرة");i=document.createElement("iframe"),i.style.position="fixed",i.style.left="-10000px",i.style.top="0",i.style.width="1000px",i.style.height="1000px",document.body.appendChild(i);const s=i.contentDocument||i.contentWindow?.document;if(!s)throw new Error("فشل في إنشاء إطار للطباعة");s.open(),s.write(N),s.close(),await new Promise(n=>setTimeout(n,500));const x=`Invoice-${o.invoice_number}.pdf`,u=await Promise.race([D().set({margin:10,filename:x,image:{type:"jpeg",quality:.98},html2canvas:{scale:2,useCORS:!0,onclone:n=>{n.querySelectorAll('link[rel="stylesheet"], style').forEach(y=>y.remove())}},jsPDF:{unit:"mm",format:"a4",orientation:"portrait"}}).from(s.body).toPdf().output("blob"),new Promise((n,y)=>setTimeout(()=>y(new Error(t?"انتهت مهلة تجهيز ملف المشاركة":"Share file generation timed out")),2e4))]),$=new File([u],x,{type:"application/pdf"});if(typeof navigator.share=="function"&&typeof navigator.canShare=="function")try{if(navigator.canShare({files:[$]})){await navigator.share({title:l,text:r,files:[$]});return}}catch{}const w=URL.createObjectURL(u);try{const n=document.createElement("a");n.href=w,n.download=x,document.body.appendChild(n),n.click(),document.body.removeChild(n)}finally{URL.revokeObjectURL(w)}}catch(l){l?.name!=="AbortError"&&alert(`حدث خطأ أثناء المشاركة: ${l.message||String(l)}`)}finally{i&&i.parentNode&&i.parentNode.removeChild(i),C(!1)}};return v?e.jsx(_,{children:e.jsx("div",{className:"flex justify-center items-center h-64",children:e.jsx("p",{children:t?"جاري تحميل تفاصيل الفاتورة...":"Loading invoice details..."})})}):g||!o?e.jsx(_,{children:e.jsx("div",{className:"flex justify-center items-center h-64",children:e.jsx("p",{className:"text-red-500",children:g||(t?"لم يتم العثور على الفاتورة":"Invoice not found")})})}):e.jsx("div",{children:e.jsx(_,{children:e.jsxs("div",{className:"flex flex-col md:flex-row justify-center gap-[30px] mt-[80px] mb-20",children:[e.jsx(O,{}),e.jsxs("div",{className:"md:w-[883px] w-full",children:[e.jsx(q,{title:`${t?"تفاصيل الفاتورة":"Invoice Details"} #${o.invoice_number}`,btn:!0,onPrint:R,onShare:P}),e.jsxs("div",{ref:h,children:[e.jsx("div",{className:"overflow-x-auto w-[100vw] md:w-[60vw] lg:w-full xl:w-[883px] md:px-0 px-[20px]",children:e.jsxs("table",{dir:t?"rtl":"ltr",className:`w-full border-separate border-spacing-y-3 mt-6 text-center min-w-[883px] ${t?"!rtl":"!ltr"}`,children:[e.jsx("thead",{}),e.jsx("tbody",{className:"bg-white",children:o.order.items.map(i=>e.jsxs("tr",{className:"h-[108px]",children:[e.jsx("td",{className:"text-[#211C4D] w-[32%] border-b font-[500] py-4",children:e.jsxs("div",{className:`flex justify-start w-[243px] h-[76px] p-1 bg-[#cbcbcb2b] border rounded-[8px] items-center gap-3 ${t?"rtl":"ltr"}`,children:[e.jsx("img",{src:i.product.main_image||"https://via.placeholder.com/75",alt:t?i.product.name_ar||i.product.name:i.product.name_en||i.product.name,className:"w-[75px] h-[76px] object-contain rounded-md"}),e.jsxs("div",{className:`w-[140px] ${t?"text-start":"text-left"}`,children:[e.jsx("p",{className:"font-[600] text-[14px] text-[#211C4D] line-clamp-2 leading-tight",title:i.product.name,children:t?i.product.name_ar||i.product.name:i.product.name_en||i.product.name}),e.jsxs("div",{className:`flex flex-col ${t?"items-end":"items-start"}`,children:[e.jsxs("p",{className:"text-[14px] text-[#6c6c80] mt-1",children:["×",i.quantity]}),i.product_option&&e.jsx("p",{className:"text-[14px] text-[#6c6c80]",children:i.product_option.value})]})]})]})}),e.jsx("td",{className:"border-b py-4",children:e.jsx("div",{className:`flex justify-center w-full items-center gap-3 ${t?"rtl":"ltr"}`,children:e.jsxs("p",{children:[i.price.toLocaleString()," ",t?"رس":"SAR"]})})}),e.jsx("td",{className:"text-[#211C4D] border-b text-center font-[500] py-4",children:e.jsx("div",{className:"w-full flex items-center justify-center",children:e.jsx("p",{children:i.quantity})})}),e.jsx("td",{className:"text-[#211C4D] border-b font-[500] py-4",children:e.jsx("div",{className:"w-full flex items-center justify-center",children:e.jsxs("p",{children:[i.total.toLocaleString()," ",t?"رس":"SAR"]})})})]},i.id))})]})}),e.jsxs("div",{className:"md:w-[883px] shadow-[0_4px_8px_rgba(0,0,0,0.2)] rounded-xl bg-white py-4 px-6 mt-4",children:[e.jsx("h2",{className:"text-[24px] font-[500] text-[#211C4D] mb-4",children:t?"تفاصيل الدفع":"Payment Details"}),e.jsxs("div",{className:"flex items-center justify-between",children:[e.jsx("p",{className:"text-[16px] font-[500] text-[#211C4D]",children:t?"المجموع الفرعي":"Subtotal"}),e.jsxs("p",{className:"font-[300] text-[16px] text-[#211C4D]",children:[j.toLocaleString()," ",t?"رس":"SAR"]})]}),e.jsxs("div",{className:"flex items-center my-5 justify-between",children:[e.jsx("p",{className:"text-[16px] font-[500] text-[#211C4D]",children:t?"الضريبة المقدرة":"Estimated Tax"}),e.jsxs("p",{className:"font-[300] text-[16px] text-[#211C4D]",children:[S.toLocaleString()," ",t?"رس":"SAR"]})]}),e.jsxs("div",{className:"flex items-center justify-between",children:[e.jsx("p",{className:"text-[16px] font-[500] text-[#211C4D]",children:t?"تكلفة الشحن":"Shipping Cost"}),e.jsxs("p",{className:"font-[300] text-[16px] text-[#211C4D]",children:[o.order.shipping?.toLocaleString()||"0"," ",t?"رس":"SAR"]})]}),d>0&&e.jsxs("div",{className:"flex items-center my-3 justify-between",children:[e.jsx("p",{className:"text-[16px] font-[500] text-green-600",children:t?"الخصم":"Discount"}),e.jsxs("p",{className:"font-[300] text-[16px] text-green-600",children:["-",d.toLocaleString()," ",t?"رس":"SAR"]})]}),p>0&&e.jsxs("div",{className:"flex items-center my-3 justify-between",children:[e.jsxs("p",{className:"text-[16px] font-[500] text-blue-600 flex items-center gap-2",children:[t?"خصم VIP":"VIP Discount",c&&e.jsx("span",{className:"text-xs bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded",children:c})]}),e.jsxs("p",{className:"font-[300] text-[16px] text-blue-600",children:["-",p.toLocaleString()," ",t?"رس":"SAR"]})]}),e.jsxs("div",{className:"flex items-center mt-6 justify-between",children:[e.jsx("p",{className:"text-[24px] font-[500] text-[#211C4D]",children:t?"المجموع الاجمالي":"Total Amount"}),e.jsxs("p",{className:"text-[24px] font-[500] text-[#211C4D]",children:[b.toLocaleString()," ",t?"رس":"SAR"]})]})]}),e.jsxs("div",{className:"md:w-[883px] flex-col md:flex-row flex items-center justify-between shadow-[0_4px_8px_rgba(0,0,0,0.2)] rounded-xl bg-white md:py-4 px-6 py-4 mt-4",children:[e.jsxs("div",{className:`text-center ${t?"md:text-start":"md:text-left"}`,children:[e.jsx("h2",{className:"text-[24px] font-[500] text-[#211C4D] mb-4",children:t?"معلومات الدفع":"Payment Information"}),e.jsx("p",{className:`text-[#211C4D] text-[16px] font-[500] md:mt-2 ${t?"md:mr-2":"md:ml-2"}`,children:t?"طريقه الدفع":"Payment Method"}),e.jsx("p",{className:`text-[24px] text-[#211C4D] font-[500] ${t?"mr-2":"ml-2"}`,children:t?o.order.payment_method?.name_ar||"بطاقة ائتمان":o.order.payment_method?.name_en||o.order.payment_method?.name_ar||"Credit Card"})]}),e.jsxs("div",{className:"mt-4 md:mt-0",children:[e.jsx("p",{className:"text-[16px] font-[500] text-[#211C4D]",children:t?"المبلغ الإجمالي":"Total Amount"}),e.jsx("p",{className:"text-[24px] font-[500] text-[#211C4D]",children:b.toLocaleString()})]}),e.jsx("div",{children:e.jsx("img",{src:"/src/assets/images/sucsespayment.png",className:"w-[100px] h-[122px] object-contain",alt:""})})]})]})]})]})})})}export{ot as default};
