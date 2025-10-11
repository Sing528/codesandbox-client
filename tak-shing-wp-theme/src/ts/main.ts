type Maybe<T> = T | null;

function ready(fn: () => void): void {
  if (document.readyState !== 'loading') {
    fn();
  } else {
    document.addEventListener('DOMContentLoaded', fn);
  }
}

function enhanceQuoteForm(): void {
  const form: Maybe<HTMLFormElement> = document.querySelector('#takshing-quote-form');
  if (!form) return;

  form.addEventListener('submit', (e) => {
    const name = (form.querySelector('input[name="name"]') as HTMLInputElement | null)?.value?.trim();
    const phone = (form.querySelector('input[name="phone"]') as HTMLInputElement | null)?.value?.trim();
    if (!name || !phone) {
      e.preventDefault();
      alert('Please provide your name and phone number.');
    }
  });
}

ready(() => {
  enhanceQuoteForm();
});
