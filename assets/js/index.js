document.addEventListener('DOMContentLoaded', function () {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const backdrop = document.getElementById('sidebarBackdrop');
    const setSidebar = (open) => {
        document.body.classList.toggle('sidebar-open', open);
        if (sidebarToggle) sidebarToggle.setAttribute('aria-expanded', String(open));
    };
    sidebarToggle?.addEventListener('click', () => setSidebar(!document.body.classList.contains('sidebar-open')));
    backdrop?.addEventListener('click', () => setSidebar(false));
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') setSidebar(false); });

    const logoutBtn = document.getElementById('logoutBtn');
    logoutBtn?.addEventListener('click', function(e) {
        e.preventDefault();
        Swal.fire({title:'Sign out?',text:'You will be logged out of the inventory system.',icon:'warning',showCancelButton:true,confirmButtonColor:'#123b5d',cancelButtonColor:'#8795a1',confirmButtonText:'Sign out'}).then((result) => {
            if (!result.isConfirmed) return;
            const form = document.createElement('form'); form.method='POST'; form.action='controllers/login/logout.php';
            const input=document.createElement('input'); input.type='hidden'; input.name='csrf_token'; input.value=document.querySelector('meta[name="csrf-token"]')?.content||'';
            form.appendChild(input); document.body.appendChild(form); form.submit();
        });
    });

    const palette = {navy:'#245779',blue:'#4791c5',red:'#d96d70',amber:'#e9b849',green:'#43a276',teal:'#35a4ad',violet:'#8b73c5',grid:'rgba(40,61,80,.08)',text:'#68798a'};
    const shared = {responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{backgroundColor:'#173a55',padding:10,displayColors:false}},scales:{y:{beginAtZero:true,ticks:{precision:0,color:palette.text},grid:{color:palette.grid},border:{display:false}},x:{ticks:{color:palette.text},grid:{display:false},border:{display:false}}}};
    function readNumber(el, name){return Math.max(0, Number.parseInt(el.dataset[name]||'0',10)||0)}
    function makeBar(id, labels, values, colors, horizontal=false, label='Records') {
        const canvas=document.getElementById(id); if(!canvas || !window.Chart)return;
        new Chart(canvas,{type:'bar',data:{labels,datasets:[{label,data:values,backgroundColor:colors,borderRadius:7,borderSkipped:false,maxBarThickness:horizontal?24:48}]},options:{...shared,indexAxis:horizontal?'y':'x',scales:horizontal?{x:{beginAtZero:true,ticks:{precision:0,color:palette.text},grid:{color:palette.grid},border:{display:false}},y:{ticks:{color:palette.text},grid:{display:false},border:{display:false}}}:shared.scales}});
    }
    const supplies=document.getElementById('suppliesColumnChart');
    if(supplies)makeBar(supplies.id,['In stock','Out of stock','Non-consumable'],[readNumber(supplies,'withStock'),readNumber(supplies,'outStock'),readNumber(supplies,'nonConsumable')],[palette.blue,palette.red,palette.amber],false,'Items');
    const transactions=document.getElementById('transactionsColumnChart');
    if(transactions)makeBar(transactions.id,['Consumable','Non-consumable'],[readNumber(transactions,'consumable'),readNumber(transactions,'nonConsumable')],[palette.green,palette.teal],false,'Transactions');
    const sme=document.getElementById('lrSmeColumnChart');
    if(sme)makeBar(sme.id,['Science equipment','Math equipment'],[readNumber(sme,'science'),readNumber(sme,'math')],[palette.blue,palette.amber],false,'Records');
    function chartArray(canvas,key){try{const val=JSON.parse(canvas.dataset[key]||'[]');return Array.isArray(val)?val:[]}catch{return []}}
    const subjects=document.getElementById('textbooksSubjectChart');
    if(subjects)makeBar(subjects.id,chartArray(subjects,'labels'),chartArray(subjects,'values'),palette.teal,true,'Quantity');
    const grades=document.getElementById('textbooksGradeChart');
    if(grades)makeBar(grades.id,chartArray(grades,'labels'),chartArray(grades,'values'),palette.amber,false,'Quantity');
});
