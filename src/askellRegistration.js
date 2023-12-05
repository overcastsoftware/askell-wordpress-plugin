import { Component } from 'react';
import { __ } from '@wordpress/i18n';

class AskellRegistration extends Component {
	constructor(props) {
		const currentYear = new Date().getFullYear();
		super(props);
		this.state = {
			blockId: _.uniqueId('askell-registration-block-'),
			currentYear,
			currentStep: 'plans',
			plans: [],
			selectedPlan: {},
			firstName: '',
			lastName: '',
			emailAddress: '',
			emailAddressIsValid: false,
			username: '',
			password: '',
			termsAccepted: false,
			userInfoChecked: false,
			userID: 0,
			cardHolderName: '',
			cardNumber: '',
			cardNumberSpaced: '',
			cardExpiryMonth: '1',
			cardExpiryYear: currentYear.toString(),
			cardIssuer: '',
			cardIssuerName: '',
			cardSecurityCode: '',
			disableConfirmButton: true,
			WpErrorCode: null,
			WpErrorMessage: null,
			disableNextStepButton: false,
			cleanKennitala: null,
			paymentWindow: null,
			paymentToken: null,
			checkPaymentTokenIntervalID: null,
			registrationToken: null,
			paymentErrorMessage: null,
			tosUrl: '',
		};
	}

	componentDidMount = () => {
		this.getFormFields();
	};

	getFormFields = async () => {
		const response = await fetch(
			wpApiSettings.root + 'askell/v1/form_fields',
			{
				method: 'GET',
				cache: 'no-cache',
			}
		);

		const result = await response.json();

		this.setState({
			APIKey: result.api_key,
			reference: result.reference,
			stylesEnabled: result.styles_enabled,
			tosUrl: result.tos_url,
			plans: result.plans,
		});

		return result;
	};

	createUser = async () => {
		this.setState({ disableNextStepButton: true });
		const response = await fetch(
			wpApiSettings.root + 'askell/v1/customer',
			{
				method: 'POST',
				cache: 'no-cache',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': wpApiSettings.nonce,
				},
				body: JSON.stringify({
					password: this.state.password,
					username: this.state.username,
					emailAddress: this.state.emailAddress,
					firstName: this.state.firstName,
					lastName: this.state.lastName,
					planId: this.state.selectedPlan.id,
					planReference: this.state.selectedPlan.reference,
					kennitala: this.state.cleanKennitala,
				}),
			}
		);

		const responseData = await response.json();

		if (response.ok) {
			this.setState({
				disableNextStepButton: false,
				userID: responseData.ID,
				registrationToken: responseData.registration_token,
				currentStep: 'cc-info',
				disableConfirmButton: false,
			});
		} else {
			this.setState({
				disableNextStepButton: false,
				WpErrorCode: responseData.code,
				WpErrorMessage: responseData.message,
			});
		}
	};

	onClickConfirmPayment = () => {
		this.openPaymentModal();
		this.createTemporaryPaymentMethod();
	};

	openPaymentModal = () => {
		const paymentWindow = window.open(
			'',
			'askell_payment_window',
			'popup,width=600,height=400'
		);

		window.paymentWindow = paymentWindow;
	};

	createTemporaryPaymentMethod = async () => {
		this.clearPaymentError();
		const response = await fetch(
			'https://askell.is/api/temporarypaymentmethod/',
			{
				method: 'POST',
				cache: 'no-cache',
				headers: {
					'Content-Type': 'application/json',
					Authorization: 'Api-Key ' + this.state.APIKey,
				},
				body: JSON.stringify({
					card_number: this.state.cardNumber,
					expiration_year: this.state.cardExpiryYear.slice(2),
					expiration_month: this.state.cardExpiryMonth,
					cvv_number: this.state.cardSecurityCode,
					plan: this.state.selectedPlan.id,
				}),
			}
		);

		const responseData = await response.json();

		if (response.ok) {
			const url = responseData.card_verification_url;
			const paymentToken = responseData.token;

			window.paymentWindow.location = url;
			this.setState({ paymentToken });

			this.checkPaymentTokenLoop(
				paymentToken,
				this.state.APIKey,
				this.state.registrationToken,
				this.state.selectedPlan.id,
				this
			);
		} else {
			this.setPaymentError(responseData.error);
			window.paymentWindow.close();
		}
	};

	checkPaymentToken = async (
		paymentToken,
		apiKey,
		registrationToken,
		planID,
		parent
	) => {
		const response = await fetch(
			'https://askell.is/api/temporarypaymentmethod/' + paymentToken,
			{
				method: 'GET',
				cache: 'no-cache',
				headers: {
					'Content-Type': 'application/json',
					Authorization: 'Api-Key ' + apiKey,
				},
			}
		);

		const responseData = await response.json();

		if (response.ok) {
			if (responseData.status !== 'initial') {
				this.setState({ disableConfirmButton: false });
				clearInterval(window.askellTokenIntervalID);

				switch (responseData.status) {
					case 'failed':
						parent.setPaymentError(
							__(
								'Card processing failed. Please check if the information if correct and try again.',
								'askell-registration'
							)
						);
						break;
					case 'tokencreated':
						parent.assignPaymentMethod(
							paymentToken,
							registrationToken,
							planID,
							parent
						);
				}
			}
		} else {
			parent.setPaymentError(responseData.error);
		}
	};

	checkPaymentTokenLoop = (
		token,
		APIKey,
		registrationToken,
		planID,
		parent
	) => {
		this.setState({ disableConfirmButton: true });

		const intervalID = setInterval(
			this.checkPaymentToken,
			2500,
			token,
			APIKey,
			registrationToken,
			planID,
			parent
		);
		window.askellTokenIntervalID = intervalID;
	};

	assignPaymentMethod = async (
		paymentToken,
		registrationToken,
		planID,
		parent
	) => {
		const response = await fetch(
			wpApiSettings.root + 'askell/v1/customer_payment_method',
			{
				method: 'POST',
				cache: 'no-cache',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': wpApiSettings.nonce,
				},
				body: JSON.stringify({
					paymentToken,
					registrationToken,
					planID,
				}),
			}
		);

		if (response.ok) {
			parent.showSuccess();
		} else {
			parent.setPaymentError(
				__(
					'Unable to assign the payment method to your account. Please contact the site owner.',
					'askell-registration'
				)
			);
		}
	};

	showSuccess = () => {
		this.setState({ currentStep: 'success' });
	};

	setPaymentError = (errorMessage) => {
		this.setState({ paymentErrorMessage: errorMessage });
	};

	clearPaymentError = () => {
		this.setState({ paymentErrorMessage: null });
	};

	onFormSubmit = (event) => {
		event.preventDefault();
	};

	onChangePlan = (event) => {
		const plan = this.state.plans.find(
			({ id }) => id === parseInt(event.target.value)
		);
		this.setState({
			selectedPlan: plan,
		});
	};

	onClickPlansNextStep = (event) => {
		event.preventDefault();
		if (this.state.currentStep === 'plans') {
			this.setState({ currentStep: 'user-info' });
		}
	};

	onChangeFirstName = (event) => {
		this.setState({ firstName: event.target.value });
	};

	onChangeLastName = (event) => {
		this.setState({ lastName: event.target.value });
	};

	onChangeEmailAddress = (event) => {
		this.setState({
			emailAddress: event.target.value,
			emailAddressIsValid: event.target.validity.valid,
		});
	};

	onChangeUsername = (event) => {
		const sanitisedUsername = event.target.value.replace(
			/([^a-z|A-Z|0-9|._-])/,
			''
		);
		this.setState({ username: sanitisedUsername });
	};

	onChangePassword = (event) => {
		this.setState({ password: event.target.value });
	};

	onChangeTermsAccepted = (event) => {
		this.setState({ termsAccepted: event.target.checked });
	};

	onClickUserInfoNextStep = (event) => {
		event.preventDefault();
		this.setState({ userInfoChecked: true });

		// Count the number of invalid elements in the user info section
		const invalidElementCount = document.querySelectorAll(
			'#' + this.state.blockId + ' .askell-user-info-form input:invalid'
		).length;

		// Show the credit card form when there are zero validation errors in
		// the user info section.
		// CSS will be used for highlighting invalid fields.
		if (invalidElementCount === 0 && this.state.termsAccepted === true) {
			this.createUser();
		}
	};

	onClickUserInfoBackButton = (event) => {
		event.preventDefault();
		this.setState({ currentStep: 'plans' });
	};

	onChangeCardHolderName = (event) => {
		this.setState({ cardHolderName: event.target.value });
	};

	onChangeCardNumber = (event) => {
		// Sanitise the card number, removing all non-numeric characters. This
		// is the value that is then sent to the API.
		const cleanCardNumber = event.target.value.replace(/[^\d.-]+/g, '');

		// Slices the card number into 4 space-separated sections, which is more
		// human readable. This is the value displayed in the form field.
		const spacedCardNumber = [
			cleanCardNumber.slice(0, 4),
			cleanCardNumber.slice(4, 8),
			cleanCardNumber.slice(8, 12),
			cleanCardNumber.slice(12, 19),
		]
			.filter((portion) => portion !== '')
			.join(' ');

		this.setState({
			cardNumber: cleanCardNumber,
			cardNumberSpaced: spacedCardNumber,
		});

		this.displayCardIssuer(cleanCardNumber);
	};

	onChangeCardExpiryMonth = (event) => {
		this.setState({ cardExpiryMonth: event.target.value });
	};

	onChangeCardExpiryYear = (event) => {
		this.setState({ cardExpiryYear: event.target.value });
	};

	onChangeCardSecurityCode = (event) => {
		const cleanCode = event.target.value
			.replace(/[^\d.-]+/g, '')
			.slice(0, 4);
		this.setState({ cardSecurityCode: cleanCode });
	};

	cardIsAmericanExpress = (cardNumber) => {
		if (cardNumber.startsWith(34) || cardNumber.startsWith(37)) {
			return true;
		}

		return false;
	};

	cardIsDinersClub = (cardNumber) => {
		if (cardNumber.startsWith(36) || cardNumber.startsWith(54)) {
			return true;
		}
		return false;
	};

	cardIsDiscover = (cardNumber) => {
		if (cardNumber.startsWith('6011')) {
			return true;
		}

		if (cardNumber.startsWith('65')) {
			return true;
		}

		if (
			parseInt(cardNumber.slice(0, 3)) >= 644 &&
			parseInt(cardNumber.slice(0, 3) <= 649)
		) {
			return true;
		}

		if (
			parseInt(cardNumber.slice(0, 6)) >= 622126 &&
			parseInt(cardNumber.slice(0, 6) <= 622925)
		) {
			return true;
		}

		return false;
	};

	cardIsMaestro = (cardNumber) => {
		const INNs = [
			6759, 676770, 676774, 5018, 5020, 5038, 5893, 6304, 6759, 6761,
			6762, 6763,
		];

		let i = 0;

		while (i < INNs.length) {
			if (cardNumber.startsWith(INNs[i])) {
				return true;
			}
			i++;
		}

		return false;
	};

	cardIsMasterCard = (cardNumber) => {
		if (
			parseInt(cardNumber.slice(0, 4) >= 2221) &&
			parseInt(cardNumber.slice(0, 4) <= 2720)
		) {
			return true;
		}

		if (
			parseInt(cardNumber.slice(0, 2)) >= 51 &&
			parseInt(cardNumber.slice(0, 2)) <= 55
		) {
			return true;
		}

		return false;
	};

	cardIsUnionPay = (cardNumber) => {
		if (cardNumber.startsWith(62)) {
			return true;
		}

		return false;
	};

	cardIsVisa = (cardNumber) => {
		if (cardNumber.startsWith(4)) {
			return true;
		}

		return false;
	};

	displayCardIssuer = (cardNumber) => {
		if (this.cardIsAmericanExpress(cardNumber)) {
			return this.setState({
				cardIssuer: 'amex',
				cardIssuerName: 'American Express',
			});
		}

		if (this.cardIsDinersClub(cardNumber)) {
			return this.setState({
				cardIssuer: 'diners',
				cardIssuerName: 'Diners Club',
			});
		}

		if (this.cardIsDiscover(cardNumber)) {
			return this.setState({
				cardIssuer: 'discover',
				cardIssuerName: 'Discover',
			});
		}

		if (this.cardIsMaestro(cardNumber)) {
			return this.setState({
				cardIssuer: 'maestro',
				cardIssuerName: 'Maestro',
			});
		}

		if (this.cardIsVisa(cardNumber)) {
			return this.setState({
				cardIssuer: 'visa',
				cardIssuerName: 'Visa',
			});
		}

		if (this.cardIsMasterCard(cardNumber)) {
			return this.setState({
				cardIssuer: 'mastercard',
				cardIssuerName: 'MasterCard',
			});
		}

		return this.setState({
			cardIssuer: '',
			cardIssuerName: '',
		});
	};

	render() {
		return (
			<form
				id={this.state.blockId}
				method="post"
				action="#"
				data-current-step={this.state.currentStep}
				onSubmit={this.onFormSubmit}
			>
				<div
					className="askell-plan-picker-form askell-step"
					onChange={this.onChangePlan}
					aria-labelledby={
						this.state.blockId + '-plan-section-heading'
					}
				>
					<span
						id={this.state.blockId + '-plan-section-heading'}
						className="section-heading"
						role="heading"
					>
						{__('Choose Your Plan', 'askell-registration')}
					</span>
					<div className="askell-form-plans">
						{this.state.plans.map((p, i) => (
							<div
								id={'askell-form-plan-container-' + i}
								className="askell-form-plan-container"
								key={p.id}
								aria-labelledby={
									this.state.blockId + '-plan-name-' + p.id
								}
							>
								<input
									id={
										this.state.blockId +
										'-plan-radio-' +
										p.id
									}
									name="plan"
									type="radio"
									value={p.id}
								/>
								<label
									className=""
									htmlFor={
										this.state.blockId +
										'-plan-radio-' +
										p.id
									}
								>
									<span
										id={
											this.state.blockId +
											'-plan-name-' +
											p.id
										}
										className="plan-name"
										role="heading"
									>
										{p.name}
									</span>
									{p.description !== '' && (
										<p className="description">
											{p.description}
										</p>
									)}
									<em className="price">{p.price_tag}</em>
								</label>
							</div>
						))}
					</div>
					<div className="buttons">
						<button
							disabled={this.state.selectedPlan.id === undefined}
							onClick={this.onClickPlansNextStep}
						>
							{__('Next Step', 'askell-registration')}
						</button>
					</div>
				</div>
				<div
					className="askell-user-info-form askell-step"
					aria-labelledby={
						this.state.blockId + '-user-info-section-heading'
					}
					data-checked={this.state.userInfoChecked}
				>
					<span
						id={this.state.blockId + '-user-info-section-heading'}
						className="section-heading"
						role="heading"
					>
						{__('Account Information', 'askell-registration')}
					</span>
					<p className="payment-info">
						{__(
							'Here, we will create a new user for you on this site. It is necessary to enter your information into all the fields below in order to get to the next step, where you will enter your payment information.',
							'askell-registration'
						)}
					</p>
					<div className="field-container">
						<div className="askell-form-first-name askell-form-field">
							<label htmlFor={this.state.blockId + '-first-name'}>
								{__('First Name', 'askell-registration')}
							</label>
							<input
								id={this.state.blockId + '-first-name'}
								name="firstName"
								type="text"
								value={this.state.firstName}
								required
								minLength="2"
								onChange={this.onChangeFirstName}
							/>
						</div>
						<div className="askell-form-last-name flex">
							<label htmlFor={this.state.blockId + '-last-name'}>
								{__('Last Name', 'askell-registration')}
							</label>
							<input
								id={this.state.blockId + '-last-name'}
								name="lastName"
								type="text"
								value={this.state.lastName}
								required
								minLength="2"
								onChange={this.onChangeLastName}
							/>
						</div>
					</div>
					<div className="askell-form-email askell-form-field">
						<label htmlFor={this.state.blockId + '-email-address'}>
							{__('Email Address', 'askell-registration')}
						</label>
						<input
							id={this.state.blockId + '-email-address'}
							name="emailAddress"
							type="email"
							value={this.state.emailAddress}
							required
							onChange={this.onChangeEmailAddress}
						/>
					</div>
					<div className="field-container">
						<div className="askell-form-username askell-form-field">
							<label htmlFor={this.state.blockId + '-username'}>
								{__('Username', 'askell-registration')}
							</label>
							<input
								id={this.state.blockId + '-username'}
								name="username"
								type="text"
								value={this.state.username}
								required
								minLength="1"
								maxLength="60"
								onChange={this.onChangeUsername}
							/>
						</div>
						<div className="askell-form-password askell-form-field">
							<label htmlFor={this.state.blockId + '-password'}>
								{__('Password', 'askell-registration')}
							</label>
							<input
								id={this.state.blockId + '-password'}
								name="password"
								type="password"
								value={this.state.password}
								required
								minLength="8"
								onChange={this.onChangePassword}
							/>
						</div>
					</div>
					<div className="askell-form-terms-checkbox">
						<input
							id={this.state.blockId + '-terms-checkbox'}
							name="termsAccepted"
							type="checkbox"
							onClick={this.onChangeTermsAccepted}
						/>
						<label
							htmlFor={this.state.blockId + '-terms-checkbox'}
							className="inline"
						>
							<a
								href={this.state.tosUrl}
								target="_blank"
								rel="noreferrer"
							>
								{__(
									'I accept the terms of service',
									'askell-registration'
								)}
							</a>
							.
						</label>
					</div>
					<div
						className={'error ' + this.state.WpErrorCode}
						role="alert"
						aria-live="assertive"
					>
						{this.state.WpErrorCode !== null && (
							<p>
								{__('Error: ', 'askell-registration') +
									this.state.WpErrorMessage}
							</p>
						)}
					</div>
					<div className="buttons">
						<button onClick={this.onClickUserInfoBackButton}>
							{__('Back', 'askell-registration')}
						</button>
						<span> </span>
						<button
							onClick={this.onClickUserInfoNextStep}
							disabled={
								!this.state.termsAccepted ||
								this.state.disableNextStepButton
							}
						>
							{__('Create Account', 'askell-registration')}
						</button>
					</div>
				</div>
				<div className="askell-cc-info-form askell-step">
					<span className="section-heading">
						{__('Payment Information', 'askell-registration')}
					</span>
					<p className="payment-info">
						{this.state.selectedPlan.payment_info}
					</p>
					<div className="askell-form-card-holder-name askell-form-field">
						<label
							htmlFor={this.state.blockId + '-card-holder-name'}
						>
							{__('Card Holder Name', 'askell-registration')}
						</label>
						<input
							id={this.state.blockId + '-card-holder-name'}
							type="text"
							name="cardHolderName"
							autoComplete="off"
							value={this.state.cardHolderName}
							onChange={this.onChangeCardHolderName}
						/>
					</div>
					<div className="askell-form-card-number askell-form-field">
						<label htmlFor={this.state.blockId + '-card-number'}>
							{__('Card Number', 'askell-registration')}
						</label>
						<div className="askell-card-number-form-field">
							<input
								id={this.state.blockId + '-card-number'}
								type="text"
								name="cardNumber"
								autoComplete="off"
								value={this.state.cardNumberSpaced}
								onChange={this.onChangeCardNumber}
							/>
							<span className={`issuer ${this.state.cardIssuer}`}>
								{this.state.cardIssuerName}
							</span>
						</div>
					</div>
					<div className="field-container">
						<div
							className="askell-form-card-expiry askell-form-field"
							aria-labelledby={
								this.state.blockId + '-expiry-label'
							}
						>
							<span
								id={this.state.blockId + '-expiry-label'}
								className="label"
							>
								{__('Card Expiry', 'askell-registration')}
							</span>
							<select
								name="cardExpiryMonth"
								aria-label="Month"
								autoComplete="false"
								defaultValue={this.state.cardExpiryMonth}
								onChange={this.onChangeCardExpiryMonth}
							>
								{[...Array(12)].map((_, i) => (
									<option key={'month-' + i + 1}>
										{i + 1}
									</option>
								))}
							</select>
							<select
								name="cardExpiryYear"
								aria-label="Year"
								autoComplete="off"
								defaultValue={this.state.cardExpiryYear}
								onChange={this.onChangeCardExpiryYear}
							>
								{[...Array(50)].map((_, i) => (
									<option
										key={
											'year-' +
											new Date().getFullYear() +
											i
										}
									>
										{this.state.currentYear + i}
									</option>
								))}
							</select>
						</div>
						<div className="askell-form-card-security-code askell-form-field">
							<label
								htmlFor={this.state.blockId + '-security-code'}
							>
								{__('Security Code', 'askell-registration')}
							</label>
							<input
								id={this.state.blockId + '-security-code'}
								type="text"
								name="cardSecurityCode"
								autoComplete="off"
								value={this.state.cardSecurityCode}
								onChange={this.onChangeCardSecurityCode}
							/>
						</div>
					</div>
					<div
						className={'error '}
						role="alert"
						aria-live="assertive"
					>
						{this.state.paymentErrorMessage !== null && (
							<p>
								{__('Error: ', 'askell-registration')}
								{this.state.paymentErrorMessage}
							</p>
						)}
					</div>
					<div className="buttons">
						<button
							disabled={this.state.disableConfirmButton}
							onClick={this.onClickConfirmPayment}
						>
							{__('Confirm Payment', 'askell-registration')}
						</button>
					</div>
					<p className="hint">
						{__(
							'Payment processing is performed by this site&apos;s owner&apos; card merchant service, via Askell by Overcast Software, which runs over a secure transport layer and is a PCI certified recurring payments platform. Payment information is sent directly to Askell for processing.',
							'askell-registration'
						)}
					</p>
				</div>
				<div className="askell-success-form askell-step">
					<span className="section-heading">Success!</span>
					<p>
						{__(
							'You should have received a confirmation email with the relevant details about how to edit or cancel your subscription.',
							'askell-registration'
						)}
					</p>
				</div>
			</form>
		);
	}
}

export default AskellRegistration;
