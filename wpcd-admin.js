/**
*	Randomly generate a string and add it as the deployment key
*/
document.addEventListener('click', function (event) {
	if (!event.target.matches('#wpcd-cd-keygen')) return;
	event.preventDefault();

	const wpcdKey = document.querySelector('#wpcd_key');
	wpcdKey.value = Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);

}, false);

/**
*	Toggle Visibility of the Key Field
*/
document.addEventListener('click', function (event) {
	if (!event.target.matches('#wpcd-key-toggle')) return;
	event.preventDefault();

	const wpcdKey = document.querySelector('#wpcd_key');
	wpcdKey.type = (wpcdKey.type == 'password' ? 'text' : 'password');

}, false);
