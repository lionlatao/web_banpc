document.querySelectorAll('form[data-confirm]').forEach(f=>f.addEventListener('submit',e=>{if(!confirm(f.dataset.confirm))e.preventDefault()}));
const builder=document.querySelector('#builder');
if(builder){
 const selects=[...builder.querySelectorAll('.part-select')],box=document.querySelector('#compatibility');let seq=0;
 const money=n=>new Intl.NumberFormat('vi-VN').format(n)+' ₫';
 const budget=document.querySelector('#build-budget'), budgetStatus=document.querySelector('#budget-status');
 function localSummary(){
  let total=selects.reduce((n,s)=>n+(s.value?Number(s.selectedOptions[0].dataset.price):0),0);
  let limit=Number(budget.value);
  budgetStatus.textContent=limit>0?(total>limit?'Vượt ngân sách: '+money(total-limit):'Còn lại: '+money(limit-total)):'Nhập ngân sách để theo dõi chi phí.';
  budgetStatus.classList.toggle('over',limit>0&&total>limit);
  document.querySelector('#build-progress').value=selects.filter(s=>s.value).length;
 }
 const search=document.querySelector('#part-search'), compatible=document.querySelector('#compatible-only');
 function filterParts(){
  const spec=slot=>{const s=selects.find(s=>s.name==='parts['+slot+']');return s.value?JSON.parse(s.selectedOptions[0].dataset.specs||'{}'):{}},cpu=spec('cpu'),board=spec('mainboard');
  selects.forEach(s=>[...s.options].forEach(o=>{
   if(!o.value)return; const data=JSON.parse(o.dataset.specs||'{}');
   const mismatch=compatible.checked&&((s.name==='parts[mainboard]'&&cpu.socket&&data.socket!==cpu.socket)||(s.name==='parts[ram]'&&board.ram_type&&data.ram_type!==board.ram_type));
   const hide=!!(mismatch||!o.textContent.toLocaleLowerCase('vi').includes(search.value.toLocaleLowerCase('vi')));
   o.hidden=hide&&!o.selected;o.disabled=hide&&!o.selected;
  }));
 }
 search.addEventListener('input',filterParts);compatible.addEventListener('change',filterParts);budget.addEventListener('input',localSummary);
 document.querySelector('#print-build').addEventListener('click',()=>{
  document.querySelector('.print-summary')?.remove();const sheet=document.createElement('div');sheet.className='print-summary';
  const append=(tag,text,parent=sheet)=>{const el=document.createElement(tag);el.textContent=text;parent.append(el);return el};
  append('h1','LionPC — Cấu hình dự kiến');append('p',new Date().toLocaleDateString('vi-VN'));
  const table=append('table','');selects.filter(s=>s.value).forEach(s=>{const row=append('tr','',table);append('th',s.closest('label').querySelector('strong').textContent,row);append('td',s.selectedOptions[0].textContent,row)});
  append('h2','Tổng: '+document.querySelector('#build-total').textContent);append('p',budgetStatus.textContent);append('p',box.textContent);append('p','Bản dự toán minh họa, không phải hóa đơn. Giá và tồn kho có thể thay đổi. Kiểm tra tương thích chỉ bao gồm các quy tắc cơ bản của đồ án.');document.body.append(sheet);window.print();
 });
 async function update(){
  filterParts();localSummary();
  const current=++seq;let total=0,count=0;
  selects.forEach(s=>{if(s.value){count++;total+=Number(s.selectedOptions[0].dataset.price)}});
  document.querySelector('#build-total').textContent=money(total);document.querySelector('#part-count').textContent=count;
  box.textContent='Đang kiểm tra…';box.className='notice';box.setAttribute('aria-busy','true');
  try{const r=await fetch(builder.dataset.check,{method:'POST',body:new FormData(builder),headers:{'Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content}});const data=await r.json();if(current!==seq)return;
   box.setAttribute('aria-busy','false');box.replaceChildren();let messages;
   if(!r.ok){messages=Object.values(data.errors||{error:[data.message||'Không kiểm tra được cấu hình.']}).flat();box.className='notice error'}
   else{messages=[...data.errors,...data.warnings];if(!data.errors.length && count)messages.unshift('✓ Chưa phát hiện xung đột trong các quy tắc đã kiểm tra.');if(count)messages.push('Nguồn đề xuất: '+data.recommended+' W (ước tính).');box.className='notice '+(data.errors.length?'error':count?'success':'');document.querySelector('#build-total').textContent=money(data.total)}
   messages.forEach(m=>{const p=document.createElement('p');p.textContent=m;box.appendChild(p)});
  }catch(e){if(current===seq){box.setAttribute('aria-busy','false');box.className='notice error';box.textContent='Không kết nối được máy chủ. Hãy thay đổi lựa chọn để thử kiểm tra lại.'}}
 }
 selects.forEach(s=>s.addEventListener('change',update));document.querySelector('#reset-build').addEventListener('click',()=>{selects.forEach(s=>s.value='');update()});update();
}

// Taste redesign: navigation, selected parts, and visible asynchronous states.
const menuToggle=document.querySelector('.menu-toggle'),shopNav=document.querySelector('#shop-navigation');
if(menuToggle&&shopNav){
 const mobile=window.matchMedia('(max-width:700px)');
 function setMenu(open){shopNav.hidden=mobile.matches&&!open;menuToggle.setAttribute('aria-expanded',String(open));}
 function syncMenu(){setMenu(!mobile.matches);}
 menuToggle.addEventListener('click',()=>setMenu(menuToggle.getAttribute('aria-expanded')!=='true'));
 document.addEventListener('keydown',e=>{if(e.key==='Escape'&&mobile.matches&&!shopNav.hidden){setMenu(false);menuToggle.focus();}});
 mobile.addEventListener('change',syncMenu);syncMenu();
}
document.querySelectorAll('.main-nav a,.admin-nav a').forEach(a=>{
 const target=new URL(a.href),here=new URL(location.href);
 if(target.pathname===here.pathname&&target.search===here.search)a.setAttribute('aria-current','page');
});
if(builder){
 const refreshRows=()=>builder.querySelectorAll('.part-select').forEach(s=>s.closest('.part-row').classList.toggle('is-selected',Boolean(s.value)));
 builder.addEventListener('change',refreshRows);document.querySelector('#reset-build').addEventListener('click',refreshRows);refreshRows();
}
const filters=document.querySelector('.catalog-filters');if(filters&&matchMedia('(max-width:700px)').matches)filters.open=false;
