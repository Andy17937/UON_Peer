/**
 * 学生问答系统 JavaScript - UTF-8支持
 * Student Q&A System JavaScript with UTF-8 Support
 */

// 确保脚本以UTF-8编码运行
document.addEventListener('DOMContentLoaded', function() {
    // 设置页面编码声明
    const metaCharset = document.querySelector('meta[charset]');
    if (!metaCharset) {
        const meta = document.createElement('meta');
        meta.setAttribute('charset', 'UTF-8');
        document.head.insertBefore(meta, document.head.firstChild);
    }
});

// 语言配置 - 支持中英文
const LANGUAGES = {
    en: {
        code: 'en',
        name: 'English',
        flag: '🇺🇸',
        rtl: false,
        translations: {
            // 主要界面
            'main-title': 'Student Q&A System',
            'welcome-text': 'Welcome to the Student Q&A System! Ask questions about dormitory, courses, grades, cafeteria, library, and get instant help.',
            'system-greeting': 'Hello! I\'m here to help you with campus-related questions. What would you like to know?',
            'input-placeholder': 'Type your question here...',
            'btn-ask': 'Ask',
            'status-ready': 'Ready',
            'status-connecting': 'Connecting...',
            'status-error': 'Connection Error',
            'question-count': 'Questions answered: {{count}}',
            'chat-title': 'Conversation',
            'clear-chat': 'Clear',
            
            // 学生ID相关
            'student-id-label': 'Student ID',
            'student-id-placeholder': '1234567',
            'student-id-hint': 'Enter your 7-digit student ID (numbers only)',
            
            // 对话框相关
            'dialog-title': 'Welcome to ASK Uon',
            'dialog-subtitle': 'Let\'s get you started',
            'welcome-message': 'Hi there! I\'m your campus assistant. Before we start, I need to verify your student identity for better personalized help.',
            'start-verification': 'Let\'s Start',
            'id-request-message': 'Perfect! Please enter your student ID. It should be in the format C followed by 7 digits (like C1234567).',
            'your-student-id': 'Your Student ID',
            'dialog-hint': 'Enter your 7-digit student ID (numbers only)',
            'back': 'Back',
            'continue': 'Continue',
            'confirmation-message': 'Great! I\'ve verified your student ID:',
            'ready-message': 'Now you\'re all set! Ask me anything about campus life, courses, facilities, or if you need support. I\'m here to help! 🎓',
            'change-id': 'Change ID',
            'start-chatting': 'Start Chatting',
            'logged-in-as': 'Logged in as:',

            'loading-text': 'Processing your question...',
            
            // 分类标签
            'dormitory': 'Dormitory',
            'courses': 'Courses',
            'grades': 'Grades',
            'cafeteria': 'Cafeteria',
            'library': 'Library',
            'counseling': 'Counseling',
            
            // 心理健康模态框
            'modal-title': 'Mental Health Support',
            'modal-message': 'We noticed you might need mental health support. Please remember that seeking help is a sign of strength, not weakness.',
            'emergency-hotline': '24/7 Crisis Hotline:',
            'campus-counseling': 'Campus Counseling:',
            'understand': 'I Understand',
            

            
            // 错误信息
            'error-network': 'Network error. Please check your connection and try again.',
            'error-server': 'Server error. Please try again later.',
            'error-invalid': 'Invalid input. Please enter a valid question.',
            'error-empty': 'Please enter a question before asking.',
            'error-too-long': 'Question is too long. Please keep it under 500 characters.',
            
            // 成功消息
            'success-sent': 'Question sent successfully!',
            'success-saved': 'Response saved to history.',
            

            'submit': 'Send',
            'thinking': 'Thinking...',
            'network-error': 'Network error, please try again later',
            'empty-question': 'Please enter a question',
            'ai-thinking': 'AI is thinking...',
            'ai-mode-indicator': 'Currently in AI Assistant mode',
            'exit-ai': 'Exit AI mode',
            'ai-placeholder': 'Ask AI Assistant...',
            'handover-to-ai': 'Hand over to AI'
        }
    },
    zh: {
        code: 'zh',
        name: '中文',
        flag: '🇨🇳',
        rtl: false,
        translations: {
            // 主要界面
            'main-title': '学生问答系统',
            'welcome-text': '欢迎使用学生问答系统！可以询问宿舍、课程、成绩、食堂、图书馆等相关问题，获得即时帮助。',
            'system-greeting': '您好！我是校园问答助手，可以帮助您解答校园生活相关问题。请问有什么需要了解的吗？',
            'input-placeholder': '请输入您的问题...',
            'btn-ask': '提问',
            'status-ready': '就绪',
            'status-connecting': '连接中...',
            'status-error': '连接错误',
            'question-count': '已回答问题：{{count}} 个',
            'chat-title': '对话记录',
            'clear-chat': '清空',
            
            // 学生ID相关
            'student-id-label': '学生ID',
            'student-id-placeholder': '1234567',
            'student-id-hint': '请输入7位数字学生ID（仅限数字）',
            
            // 对话框相关
            'dialog-title': '欢迎使用ASK Uon',
            'dialog-subtitle': '让我们开始吧',
            'welcome-message': '你好！我是你的校园助手。在开始之前，我需要验证你的学生身份以提供更好的个性化帮助。',
            'start-verification': '开始验证',
            'id-request-message': '很好！请输入你的学生ID。格式应该是C开头后跟7位数字（如C1234567）。',
            'your-student-id': '你的学生ID',
            'dialog-hint': '请输入7位数字学生ID（仅限数字）',
            'back': '返回',
            'continue': '继续',
            'confirmation-message': '太好了！我已经验证了你的学生ID：',
            'ready-message': '现在你已经准备好了！问我任何关于校园生活、课程、设施的问题，或者如果需要支持。我在这里帮助你！🎓',
            'change-id': '更改ID',
            'start-chatting': '开始聊天',
            'logged-in-as': '当前身份：',

            'loading-text': '正在处理您的问题...',
            
            // 分类标签
            'dormitory': '宿舍管理',
            'courses': '课程安排',
            'grades': '成绩查询',
            'cafeteria': '食堂信息',
            'library': '图书馆',
            'counseling': '心理咨询',
            
            // 心理健康模态框
            'modal-title': '心理健康支持',
            'modal-message': '我们注意到您可能需要心理健康支持。请记住，寻求帮助是勇敢的表现，而不是软弱。',
            'emergency-hotline': '24小时危机热线：',
            'campus-counseling': '校园心理咨询：',
            'understand': '我知道了',
            

            
            // 错误信息
            'error-network': '网络错误，请检查网络连接后重试。',
            'error-server': '服务器错误，请稍后重试。',
            'error-invalid': '输入无效，请输入有效的问题。',
            'error-empty': '请先输入问题再提交。',
            'error-too-long': '问题太长，请控制在500字符以内。',
            
            // 成功消息
            'success-sent': '问题发送成功！',
            'success-saved': '回复已保存到历史记录。',
            

            'submit': '发送',
            'thinking': '正在思考...',
            'network-error': '网络错误，请稍后重试',
            'empty-question': '请输入问题',
            'ai-thinking': 'AI正在思考...',
            'ai-mode-indicator': '当前为AI助手模式',
            'exit-ai': '退出AI模式',
            'ai-placeholder': '向AI助手提问...',
            'handover-to-ai': '转交AI助手'
        }
    }
};

// 应用程序状态
class AppState {
    constructor() {
        this.currentLanguage = this.detectLanguage();
        this.questionCount = 0;
        this.isConnected = true;
        this.isLoading = false;
        this.chatHistory = [];
        this.sessionId = this.generateSessionId();
        this.isAIMode = false;
        this.aiCategory = null;
        this.studentId = null;
        this.isStudentVerified = false;
        
        // 从本地存储恢复状态
        this.loadFromStorage();
    }
    
    detectLanguage() {
        // 检查本地存储
        const saved = localStorage.getItem('qa-language');
        if (saved && LANGUAGES[saved]) {
            return saved;
        }
        
        // 检查浏览器语言
        const browserLang = navigator.language || navigator.userLanguage || 'en';
        
        // 只有zh开头的语言使用中文，其他都使用英文
        if (browserLang.toLowerCase().startsWith('zh')) {
            return 'zh';
        }
        
        return 'en';
    }
    
    generateSessionId() {
        return 'qa-session-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
    }
    
    saveToStorage() {
        try {
            const data = {
                language: this.currentLanguage,
                questionCount: this.questionCount,
                chatHistory: this.chatHistory.slice(-50), // 只保存最近50条
                sessionId: this.sessionId,
                isAIMode: this.isAIMode,
                aiCategory: this.aiCategory,
                studentId: this.studentId,
                isStudentVerified: this.isStudentVerified,
                lastUpdated: new Date().toISOString()
            };
            localStorage.setItem('qa-app-state', JSON.stringify(data));
            localStorage.setItem('qa-language', this.currentLanguage);
        } catch (error) {
            console.warn('Failed to save to localStorage:', error);
        }
    }
    
    loadFromStorage() {
        try {
            const saved = localStorage.getItem('qa-app-state');
            if (saved) {
                const data = JSON.parse(saved);
                this.questionCount = data.questionCount || 0;
                this.chatHistory = data.chatHistory || [];
                this.isAIMode = data.isAIMode || false;
                this.aiCategory = data.aiCategory || null;
                this.studentId = data.studentId || null;
                this.isStudentVerified = data.isStudentVerified || false;
                if (data.sessionId) {
                    this.sessionId = data.sessionId;
                }
            }
        } catch (error) {
            console.warn('Failed to load from localStorage:', error);
        }
    }
}

// 国际化管理器
class I18nManager {
    constructor(appState) {
        this.appState = appState;
        this.currentLang = appState.currentLanguage;
    }
    
    t(key, params = {}) {
        const translation = LANGUAGES[this.currentLang]?.translations[key] || 
                          LANGUAGES['en'].translations[key] || 
                          key;
        
        // 替换参数 {{param}}
        return translation.replace(/\{\{(\w+)\}\}/g, (match, param) => {
            return params[param] !== undefined ? params[param] : match;
        });
    }
    
    switchLanguage(langCode) {
        if (LANGUAGES[langCode]) {
            this.currentLang = langCode;
            this.appState.currentLanguage = langCode;
            this.appState.saveToStorage();
            this.updateUI();
        }
    }
    
    updateUI() {
        // 更新页面语言属性
        document.documentElement.lang = this.currentLang;
        
        // 更新所有带有 data-i18n 属性的元素
        document.querySelectorAll('[data-i18n]').forEach(element => {
            const key = element.getAttribute('data-i18n');
            element.textContent = this.t(key);
        });
        
        // 更新预设问题按钮（如果 UIManager 可用）
        if (this.uiManager && typeof this.uiManager.renderPresetQuestionButtons === 'function') {
            this.uiManager.renderPresetQuestionButtons();
        }
        
        // 更新具有特定 ID 的元素
        const updates = {
            'main-title': 'main-title',
            'welcome-text': 'welcome-text',
            'system-greeting': 'system-greeting',
            'btn-text': 'btn-ask',
            'status-text': 'status-ready',
            'suggestions-header': 'suggestions-header',
            'loading-text': 'loading-text',
            'modal-title': 'modal-title',
            'modal-message': 'modal-message'
        };
        
        Object.entries(updates).forEach(([id, key]) => {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = this.t(key);
            }
        });
        
        // 更新输入框占位符
        const input = document.getElementById('question-input');
        if (input) {
            input.placeholder = this.t('input-placeholder');
        }
        
        // 更新语言切换按钮
        const langText = document.getElementById('lang-text');
        if (langText) {
            langText.textContent = this.currentLang === 'zh' ? 'English' : '中文';
        }
        
        // 更新问题计数
        this.updateQuestionCount();
        
        // 更新建议关键词

    }
    
    updateQuestionCount() {
        const countElement = document.getElementById('question-count');
        if (countElement) {
            countElement.textContent = this.t('question-count', { 
                count: this.appState.questionCount 
            });
        }
    }
    

    

}

// API 管理器
class APIManager {
    constructor(appState, i18n) {
        this.appState = appState;
        this.i18n = i18n;
        this.baseURL = './api';
        this.timeout = 10000; // 10秒超时
    }
    
    async makeRequest(url, options = {}) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), this.timeout);
        
        try {
            const defaultOptions = {
                headers: {
                    'Content-Type': 'application/json; charset=UTF-8',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                signal: controller.signal
            };
            
            const response = await fetch(url, { ...defaultOptions, ...options });
            clearTimeout(timeoutId);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const text = await response.text();
            
            // 确保响应是UTF-8编码
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('JSON parse error:', e, 'Response text:', text);
                throw new Error('Invalid JSON response from server');
            }
            
            return data;
            
        } catch (error) {
            clearTimeout(timeoutId);
            
            if (error.name === 'AbortError') {
                throw new Error(this.i18n.t('error-network'));
            }
            
            console.error('API Request failed:', error);
            throw error;
        }
    }
    
    async submitQuestion(question, studentId = null) {
        // 收集浏览器指纹信息
        const browserInfo = this.getBrowserFingerprint();
        
        const data = {
            question: question.trim(),
            student_id: studentId,
            language: this.appState.currentLanguage,
            session_id: this.appState.sessionId,
            browser_info: browserInfo,
            timestamp: new Date().toISOString()
        };
        
        return this.makeRequest(`${this.baseURL}/questions.php`, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }
    
    async getStats() {
        return this.makeRequest(`${this.baseURL}/questions.php?action=stats`);
    }
    
    async getRecentQuestions(limit = 10) {
        return this.makeRequest(`${this.baseURL}/questions.php?action=recent&limit=${limit}`);
    }
    
    async testConnection() {
        return this.makeRequest(`${this.baseURL}/questions.php?action=test`);
    }

    async getPresetQuestions() {
        // 添加时间戳参数防止缓存
        const timestamp = new Date().getTime();
        return this.makeRequest(`${this.baseURL}/questions.php?action=preset_questions&t=${timestamp}`);
    }
    
    // 获取浏览器指纹信息
    getBrowserFingerprint() {
        return {
            screen_resolution: `${screen.width}x${screen.height}`,
            screen_color_depth: screen.colorDepth,
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            language: navigator.language,
            languages: navigator.languages.join(','),
            platform: navigator.platform,
            cookie_enabled: navigator.cookieEnabled,
            viewport: `${window.innerWidth}x${window.innerHeight}`,
            user_agent: navigator.userAgent,
            do_not_track: navigator.doNotTrack,
            hardware_concurrency: navigator.hardwareConcurrency || 0,
            device_memory: navigator.deviceMemory || 0,
            max_touch_points: navigator.maxTouchPoints || 0,
            connection_type: navigator.connection ? navigator.connection.effectiveType : 'unknown',
            timestamp: Date.now()
        };
    }
    
    async submitQuestionToAI(question, category = 'general', studentId = null) {
        try {
            // 收集浏览器指纹信息
            const browserInfo = this.getBrowserFingerprint();
            
            const response = await this.makeRequest(`${this.baseURL}/questions.php?action=ai_chat`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    question: question,
                    student_id: studentId,
                    category: category,
                    language: this.appState.currentLanguage,
                    session_id: this.appState.sessionId,
                    browser_info: browserInfo
                })
            });
            
            return response;
        } catch (error) {
            console.error('AI question submission failed:', error);
            throw error;
        }
    }
}

// UI 管理器
class UIManager {
    constructor(appState, i18n, api) {
        this.appState = appState;
        this.i18n = i18n;
        this.api = api;
        
        this.elements = {};
        this.presetQuestions = {}; // 缓存预设问题
        this.cacheElements();
        this.setupEventListeners();
        this.loadPresetQuestions(); // 初始化时加载预设问题
        
        // 定期重新加载预设问题（每5分钟）
        setInterval(() => {
            this.loadPresetQuestions();
        }, 5 * 60 * 1000);
        
        // 当窗口获得焦点时重新加载预设问题
        window.addEventListener('focus', () => {
            this.loadPresetQuestions();
        });
    }
    
    cacheElements() {
        this.elements = {
            form: document.getElementById('question-form'),
            input: document.getElementById('question-input'),
            submitBtn: document.getElementById('submit-btn'),
            chatHistory: document.getElementById('chat-history'),
            qaSection: document.getElementById('qa-section'),
            clearChatBtn: document.getElementById('clear-chat-btn'),
            loadingOverlay: document.getElementById('loading-overlay'),
            errorToast: document.getElementById('error-toast'),
            langToggle: document.getElementById('lang-toggle'),
            charCounter: document.getElementById('char-counter'),
            statusDot: document.querySelector('.status-dot'),
            statusText: document.getElementById('status-text'),
            psychologyModal: document.getElementById('psychology-modal'),
            modalClose: document.getElementById('modal-close'),
            modalUnderstand: document.getElementById('modal-understand'),
            toastClose: document.getElementById('toast-close'),
            
            // 学生ID对话框相关
            studentIdModal: document.getElementById('student-id-modal'),
            stepWelcome: document.getElementById('step-welcome'),
            stepStudentId: document.getElementById('step-student-id'),
            stepConfirmation: document.getElementById('step-confirmation'),
            startVerification: document.getElementById('start-verification'),
            backToWelcome: document.getElementById('back-to-welcome'),
            dialogStudentId: document.getElementById('dialog-student-id'),
            dialogHint: document.getElementById('dialog-hint'),
            continueWithId: document.getElementById('continue-with-id'),
            confirmedStudentId: document.getElementById('confirmed-student-id'),
            changeStudentIdBtn: document.getElementById('change-student-id'),
            startChatting: document.getElementById('start-chatting'),
            
            // 学生信息条
            studentInfoBar: document.getElementById('student-info-bar'),
            currentStudentId: document.getElementById('current-student-id'),
            changeIdBtn: document.getElementById('change-id-btn')
        };
    }
    
    setupEventListeners() {
        // 表单提交
        this.elements.form?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleQuestionSubmit();
        });
        
        // 输入监听
        this.elements.input?.addEventListener('input', () => {
            this.updateCharCounter();
            this.updateSubmitButton();
        });
        
        // 预设问题标签点击
        document.querySelectorAll('.tag').forEach(tag => {
            tag.addEventListener('click', () => {
                const category = tag.getAttribute('data-category');
                this.handlePresetQuestion(category);
            });
        });
        

        
        // 语言切换
        this.elements.langToggle?.addEventListener('click', () => {
            const newLang = this.appState.currentLanguage === 'zh' ? 'en' : 'zh';
            this.i18n.switchLanguage(newLang);
        });
        
        // 模态框关闭
        this.elements.modalClose?.addEventListener('click', () => {
            this.hideModal();
        });
        
        this.elements.modalUnderstand?.addEventListener('click', () => {
            this.hideModal();
        });
        
        this.elements.psychologyModal?.addEventListener('click', (e) => {
            if (e.target === this.elements.psychologyModal) {
                this.hideModal();
            }
        });
        
        // 错误提示关闭
        this.elements.toastClose?.addEventListener('click', () => {
            this.hideError();
        });
        
        // 清空聊天按钮
        this.elements.clearChatBtn?.addEventListener('click', () => {
            this.clearChat();
        });
        
        // 键盘快捷键
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + Enter 提交
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                e.preventDefault();
                this.handleQuestionSubmit();
            }
            
            // Escape 关闭模态框
            if (e.key === 'Escape') {
                this.hideModal();
                this.hideError();
            }
        });
        
        // 学生ID对话框事件监听器
        this.setupStudentIdDialog();
        

    }
    
    updateCharCounter() {
        const input = this.elements.input;
        const counter = this.elements.charCounter;
        
        if (input && counter) {
            const length = input.value.length;
            const max = input.maxLength || 500;
            counter.textContent = `${length}/${max}`;
            
            // 警告颜色
            if (length > max * 0.9) {
                counter.style.color = 'var(--error-color)';
            } else if (length > max * 0.7) {
                counter.style.color = 'var(--warning-color)';
            } else {
                counter.style.color = 'var(--text-muted)';
            }
        }
    }
    
    updateSubmitButton() {
        const input = this.elements.input;
        const btn = this.elements.submitBtn;
        
        if (input && btn) {
            const hasText = input.value.trim().length > 0;
            const isQuestionValid = input.value.length <= (input.maxLength || 500);
            const hasStudentId = this.appState.isStudentVerified && this.appState.studentId;
            
            btn.disabled = !hasText || !isQuestionValid || !hasStudentId || this.appState.isLoading;
        }
    }
    
    // 设置学生ID对话框
    setupStudentIdDialog() {
        // 开始验证按钮
        this.elements.startVerification?.addEventListener('click', () => {
            this.showStep('step-student-id');
        });
        
        // 返回按钮
        this.elements.backToWelcome?.addEventListener('click', () => {
            this.showStep('step-welcome');
        });
        
        // 学生ID输入验证
        this.elements.dialogStudentId?.addEventListener('input', (e) => {
            this.validateDialogStudentId(e);
        });
        
        this.elements.dialogStudentId?.addEventListener('keypress', (e) => {
            this.restrictStudentIdInput(e);
        });
        
        // 继续按钮
        this.elements.continueWithId?.addEventListener('click', () => {
            this.confirmStudentId();
        });
        
        // 更改ID按钮
        this.elements.changeStudentIdBtn?.addEventListener('click', () => {
            this.showStep('step-student-id');
        });
        
        // 开始聊天按钮
        this.elements.startChatting?.addEventListener('click', () => {
            this.startChatSession();
        });
        
        // 信息条更改ID按钮
        this.elements.changeIdBtn?.addEventListener('click', () => {
            this.showStudentIdDialog();
        });
    }
    
    // 显示对话框步骤
    showStep(stepId) {
        // 隐藏所有步骤
        this.elements.stepWelcome.style.display = 'none';
        this.elements.stepStudentId.style.display = 'none';
        this.elements.stepConfirmation.style.display = 'none';
        
        // 显示指定步骤
        const stepElement = document.getElementById(stepId);
        if (stepElement) {
            stepElement.style.display = 'block';
            
            // 如果是学生ID输入步骤，聚焦到输入框
            if (stepId === 'step-student-id') {
                setTimeout(() => {
                    this.elements.dialogStudentId?.focus();
                }, 100);
            }
        }
    }
    
    // 显示学生ID对话框
    showStudentIdDialog() {
        this.elements.studentIdModal.classList.add('active');
        this.showStep('step-welcome');
        
        // 清空输入框
        if (this.elements.dialogStudentId) {
            this.elements.dialogStudentId.value = '';
        }
        this.updateDialogHint('');
        this.updateContinueButton();
    }
    
    // 限制学生ID输入只能是数字
    restrictStudentIdInput(event) {
        const char = String.fromCharCode(event.which);
        if (!/[0-9]/.test(char)) {
            event.preventDefault();
        }
    }
    
    // 验证对话框中的学生ID
    validateDialogStudentId(event) {
        const input = event.target;
        const value = input.value;
        
        // 只保留数字
        const cleanValue = value.replace(/[^0-9]/g, '');
        
        // 限制长度为7位
        const limitedValue = cleanValue.substring(0, 7);
        
        // 更新输入框值
        if (input.value !== limitedValue) {
            input.value = limitedValue;
        }
        
        // 更新提示信息
        this.updateDialogHint(limitedValue);
        this.updateContinueButton();
    }
    
    // 更新对话框提示信息
    updateDialogHint(value) {
        const hint = this.elements.dialogHint;
        if (!hint) return;
        
        if (value.length === 0) {
            hint.textContent = this.i18n.t('dialog-hint');
            hint.className = 'dialog-hint';
        } else if (value.length < 7) {
            const remaining = 7 - value.length;
            const message = this.appState.currentLanguage === 'zh' 
                ? `还需要输入${remaining}位数字` 
                : `${remaining} more digits needed`;
            hint.textContent = message;
            hint.className = 'dialog-hint warning';
        } else if (value.length === 7) {
            const message = this.appState.currentLanguage === 'zh' 
                ? '✓ 学生ID格式正确' 
                : '✓ Student ID format correct';
            hint.textContent = message;
            hint.className = 'dialog-hint success';
        }
    }
    
    // 更新继续按钮状态
    updateContinueButton() {
        const btn = this.elements.continueWithId;
        const input = this.elements.dialogStudentId;
        
        if (btn && input) {
            const isValid = /^[0-9]{7}$/.test(input.value);
            btn.disabled = !isValid;
        }
    }
    
    // 确认学生ID
    confirmStudentId() {
        const studentId = this.elements.dialogStudentId.value;
        if (/^[0-9]{7}$/.test(studentId)) {
            this.appState.studentId = studentId;
            this.appState.isStudentVerified = true;
            this.appState.saveToStorage();
            
            // 更新确认信息
            this.elements.confirmedStudentId.textContent = `C${studentId}`;
            
            // 显示确认步骤
            this.showStep('step-confirmation');
        }
    }
    
    // 开始聊天会话
    startChatSession() {
        // 隐藏对话框
        this.elements.studentIdModal.classList.remove('active');
        
        // 显示问答区域
        this.elements.qaSection.style.display = 'block';
        
        // 更新学生信息条
        this.elements.currentStudentId.textContent = `C${this.appState.studentId}`;
        
        // 更新系统欢迎消息
        this.updateSystemGreeting();
        
        // 确保提交按钮状态正确
        this.updateSubmitButton();
    }
    
    // 更新系统欢迎消息
    updateSystemGreeting() {
        const greeting = document.getElementById('system-greeting');
        if (greeting) {
            const studentDisplay = `C${this.appState.studentId}`;
            const message = this.appState.currentLanguage === 'zh' 
                ? `你好 ${studentDisplay}！我是校园问答助手，可以帮助您解答校园生活相关问题。请问有什么需要了解的吗？`
                : `Hello ${studentDisplay}! I'm your campus assistant. I can help you with campus-related questions. What would you like to know?`;
            greeting.textContent = message;
        }
    }

    async loadPresetQuestions() {
        try {
            const response = await this.api.getPresetQuestions();
            if (response.success && response.data) {
                // 将数组转换为按 category 分组的对象
                this.presetQuestions = {};
                response.data.forEach(item => {
                    this.presetQuestions[item.category] = item;
                });
                console.log('Preset questions loaded:', this.presetQuestions);
                // 渲染预设问题按钮
                this.renderPresetQuestionButtons();
            } else {
                console.error('Failed to load preset questions:', response.error);
                // 使用默认的预设问题作为后备
                this.presetQuestions = this.getDefaultPresetQuestions();
                this.renderPresetQuestionButtons();
            }
        } catch (error) {
            console.error('Error loading preset questions:', error);
            // 使用默认的预设问题作为后备
            this.presetQuestions = this.getDefaultPresetQuestions();
            this.renderPresetQuestionButtons();
        }
    }
    
    renderPresetQuestionButtons() {
        const container = document.getElementById('feature-tags');
        if (!container) {
            console.warn('feature-tags container not found');
            return;
        }
        
        // 清空现有按钮
        container.innerHTML = '';
        
        // 检查 presetQuestions 是否为空
        if (!this.presetQuestions || Object.keys(this.presetQuestions).length === 0) {
            console.warn('No preset questions available');
            return;
        }
        
        console.log('Rendering preset question buttons:', this.presetQuestions);
        
        // 获取当前语言
        const currentLang = this.appState.currentLanguage;
        
        // 图标映射
        const iconMap = {
            'dormitory': '🏠',
            'course': '📚',
            'courses': '📚',
            'grade': '📊',
            'grades': '📊',
            'cafeteria': '🍽️',
            'library': '📖',
            'counseling': '💚',
            'psychology': '💚'
        };
        
        // 遍历预设问题，创建按钮
        Object.keys(this.presetQuestions).forEach(category => {
            const data = this.presetQuestions[category];
            const icon = iconMap[category] || data.category_icon || '📁';
            const displayName = currentLang === 'zh' ? data.category_name_zh : data.category_name_en;
            
            // 创建按钮
            const tag = document.createElement('span');
            tag.className = 'tag';
            tag.setAttribute('data-category', category);
            tag.innerHTML = `${icon} <span data-i18n="${category}">${displayName}</span>`;
            
            // 添加点击事件监听器
            tag.addEventListener('click', () => {
                console.log('Preset question button clicked:', category);
                this.handlePresetQuestion(category);
            });
            
            container.appendChild(tag);
        });
        
        console.log(`Rendered ${Object.keys(this.presetQuestions).length} preset question buttons`);
    }

    getDefaultPresetQuestions() {
        return {
            'dormitory': {
                'category_icon': 'house',
                'category_name_zh': '宿舍',
                'category_name_en': 'Dormitory',
                'questions_zh': ['宿舍门禁时间是什么时候？', '如何申请宿舍钥匙？', '宿舍熄灯时间是几点？'],
                'questions_en': ['What are the dormitory curfew hours?', 'How to apply for dormitory keys?', 'What time are lights out in the dormitory?']
            },
            'course': {
                'category_icon': 'book',
                'category_name_zh': '课程',
                'category_name_en': 'Courses',
                'questions_zh': ['如何查看课程表？', '如何申请调课？', '期末考试时间安排'],
                'questions_en': ['How to check course schedules?', 'How to apply for course changes?', 'Final exam schedule']
            },
            'grade': {
                'category_icon': 'chart',
                'category_name_zh': '成绩',
                'category_name_en': 'Grades',
                'questions_zh': ['如何查询成绩？', '成绩复查流程', 'GPA如何计算？'],
                'questions_en': ['How to check grades?', 'Grade review process', 'How is GPA calculated?']
            },
            'cafeteria': {
                'category_icon': 'food',
                'category_name_zh': '食堂',
                'category_name_en': 'Cafeteria',
                'questions_zh': ['食堂营业时间', '校园卡充值方式', '今日菜单'],
                'questions_en': ['Cafeteria operating hours', 'Campus card recharge methods', 'Today\'s menu']
            },
            'library': {
                'category_icon': 'library',
                'category_name_zh': '图书馆',
                'category_name_en': 'Library',
                'questions_zh': ['图书馆开放时间', '如何预约座位？', '借书期限多长？'],
                'questions_en': ['Library opening hours', 'How to reserve seats?', 'How long is the borrowing period?']
            },
            'counseling': {
                'category_icon': 'heart',
                'category_name_zh': '心理咨询',
                'category_name_en': 'Counseling',
                'questions_zh': ['心理咨询预约方式', '心理健康资源', '如何寻求帮助？'],
                'questions_en': ['How to book counseling?', 'Mental health resources', 'How to seek help?']
            }
        };
    }
    
    handlePresetQuestion(category) {
        // 使用动态加载的预设问题
        const categoryData = this.presetQuestions[category];
        
        if (!categoryData) {
            console.warn(`No preset questions found for category: ${category}`);
            return;
        }
        
        const currentLang = this.appState.currentLanguage;
        const questions = currentLang === 'zh' ? categoryData.questions_zh : categoryData.questions_en;
        
        if (questions && questions.length > 0) {
            const randomQuestion = questions[Math.floor(Math.random() * questions.length)];
            
            // 将问题填入输入框
            if (this.elements.input) {
                this.elements.input.value = randomQuestion;
                this.updateCharCounter();
                this.updateSubmitButton();
                this.elements.input.focus();
            }
        } else {
            console.warn(`No questions found for category: ${category}, language: ${currentLang}`);
        }
    }
    
    async handleQuestionSubmit() {
        const question = this.elements.input?.value?.trim();
        const studentId = this.appState.studentId;
        
        if (!question) {
            this.showError(this.i18n.t('error-empty'));
            return;
        }
        
        if (!this.appState.isStudentVerified || !studentId) {
            const errorMsg = this.appState.currentLanguage === 'zh' 
                ? '请先验证学生ID' 
                : 'Please verify your student ID first';
            this.showError(errorMsg);
            return;
        }
        
        if (question.length > 500) {
            this.showError(this.i18n.t('error-too-long'));
            return;
        }
        
        try {
            // 设置加载状态（但不显示全屏加载）
            this.appState.isLoading = true;
            this.updateSubmitButton();
            this.updateConnectionStatus(true, this.i18n.t('status-connecting'));
            
            // 清除之前的操作按钮
            this.clearActionButtons();
            
            // 添加用户消息到聊天
            this.addMessage('user', question);
            
            // 清空输入框
            this.elements.input.value = '';
            this.updateCharCounter();
            this.updateSubmitButton();
            
            // 添加"正在思考"的机器人消息
            const thinkingMessageId = this.addThinkingMessage();
            
            // 随机延迟 1-5 秒模拟AI思考时间
            const delay = Math.random() * 4000 + 1000; // 1000ms-5000ms
            
            // 根据是否为AI模式选择不同的API调用
            let apiCall;
            if (this.appState.isAIMode) {
                apiCall = this.api.submitQuestionToAI(question, this.appState.aiCategory, studentId);
            } else {
                apiCall = this.api.submitQuestion(question, studentId);
            }
            
            // 并行执行API请求和延迟
            const [response] = await Promise.all([
                apiCall,
                new Promise(resolve => setTimeout(resolve, delay))
            ]);
            
            // 移除"正在思考"消息
            this.removeThinkingMessage(thinkingMessageId);
            
            if (response.success) {
                // 创建助手回复消息并使用打字机效果显示
                const assistantMessageElement = this.createAssistantMessage({
                    isPsychology: response.is_psychology_related,
                    category: response.category,
                    isAI: response.is_ai_response || false
                });
                
                // 如果是AI回复，添加特殊标识
                if (response.is_ai_response) {
                    assistantMessageElement.setAttribute('data-ai', 'true');
                }
                
                // 使用打字机效果显示回答
                await this.typeWriterEffect(assistantMessageElement, response.response, 5);
                
                // 添加助手消息功能（按钮等）
                const messageMeta = {
                    isPsychology: response.is_psychology_related,
                    category: response.category,
                    isAI: response.is_ai_response || false,
                    links: response.links || []
                };
                
                this.addAssistantMessageFeatures(assistantMessageElement, messageMeta);
                
                // 保存到历史记录
                this.saveMessageToHistory('assistant', response.response, messageMeta);
                
                // 更新统计
                this.appState.questionCount++;
                this.i18n.updateQuestionCount();
                
                // 如果是心理健康相关且不是AI回复，显示支持模态框
                if (response.is_psychology_related && !response.is_ai_response) {
                    setTimeout(() => this.showPsychologyModal(), 1000);
                }
                
                // 保存状态
                this.appState.saveToStorage();
                
            } else {
                throw new Error(response.error || this.i18n.t('error-server'));
            }
            
            this.updateConnectionStatus(true, this.i18n.t('status-ready'));
            
        } catch (error) {
            console.error('Question submission failed:', error);
            this.showError(error.message || this.i18n.t('error-network'));
            this.updateConnectionStatus(false, this.i18n.t('status-error'));
        } finally {
            this.appState.isLoading = false;
            this.updateSubmitButton();
        }
    }
    
    addMessage(type, content, meta = {}, skipSaveToHistory = false) {
        const chatHistory = this.elements.chatHistory;
        if (!chatHistory) return;
        
        const messageDiv = document.createElement('div');
        messageDiv.className = `chat-message ${type}-message`;
        
        if (meta.isPsychology) {
            messageDiv.classList.add('psychology-alert');
        }
        
        const icon = type === 'user' ? '👤' : 
                    type === 'system' ? '🤖' : 
                    meta.isPsychology ? '<img src="/resources/ai_avatar.jpeg" alt="AI" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">' : '🤖';
        
        const messageContent = document.createElement('div');
        messageContent.className = 'message-content';
        messageContent.innerHTML = `
            <span class="${type}-icon">${icon}</span>
            <div class="message-text">${this.escapeHtml(content)}</div>
        `;
        
        // AI相关标识会在addAssistantMessageFeatures中处理
        if (type === 'assistant' && meta.isAI) {
            messageDiv.setAttribute('data-ai', 'true');
        }
        

        
        messageDiv.appendChild(messageContent);
        chatHistory.appendChild(messageDiv);
        
        // 只有在不是恢复历史记录时才保存到数组
        if (!skipSaveToHistory) {
            this.appState.chatHistory.push({
                type,
                content,
                timestamp: new Date().toISOString(),
                meta
            });
        }
        
        // 更新清空按钮状态
        this.updateClearButtonVisibility();
        
        // 滚动到底部
        this.scrollToBottom();
    }
    
    addThinkingMessage() {
        const chatHistory = this.elements.chatHistory;
        if (!chatHistory) return null;
        
        const messageId = 'thinking-' + Date.now();
        const messageDiv = document.createElement('div');
        messageDiv.className = 'chat-message assistant-message thinking-message';
        messageDiv.id = messageId;
        
        const messageContent = document.createElement('div');
        messageContent.className = 'message-content';
        
        const thinkingText = this.appState.currentLanguage === 'zh' ? '正在思考中' : 'Thinking';
        
        messageContent.innerHTML = `
            <span class="assistant-icon">🤖</span>
            <div class="message-text thinking-indicator">
                <span class="thinking-text">${thinkingText}</span>
                <div class="thinking-dots">
                    <span class="dot"></span>
                    <span class="dot"></span>
                    <span class="dot"></span>
                </div>
            </div>
        `;
        
        messageDiv.appendChild(messageContent);
        chatHistory.appendChild(messageDiv);
        
        // 滚动到底部
        this.scrollToBottom();
        
        return messageId;
    }
    
    removeThinkingMessage(messageId) {
        if (messageId) {
            const thinkingMessage = document.getElementById(messageId);
            if (thinkingMessage) {
                thinkingMessage.remove();
            }
        }
    }
    
    // 逐词显示回答
    async typeWriterEffect(messageElement, fullText, speed = 5) {
        const textElement = messageElement.querySelector('.message-text');
        if (!textElement) return;
        
        // 清空当前内容
        textElement.innerHTML = '';
        
        // 将换行符转换为 <br> 标签，保留格式
        const formattedText = this.escapeHtml(fullText).replace(/\n/g, '<br>');
        
        // 创建一个临时容器来解析 HTML
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = formattedText;
        
        // 获取所有文本节点和 br 元素
        const nodes = this.getTextNodes(tempDiv);
        
        // 逐个显示节点内容
        for (const node of nodes) {
            if (node.nodeType === Node.TEXT_NODE) {
                // 文本节点：逐词显示
                const words = this.splitTextByWords(node.textContent);
                for (const word of words) {
                    const textNode = document.createTextNode(word);
                    textElement.appendChild(textNode);
                    this.scrollToBottom();
                    await this.delay(speed);
                }
            } else if (node.nodeName === 'BR') {
                // 换行标签：直接添加
                textElement.appendChild(document.createElement('br'));
                this.scrollToBottom();
            }
        }
    }
    
    // 获取所有文本节点和 br 元素（保持顺序）
    getTextNodes(element) {
        const nodes = [];
        const walker = document.createTreeWalker(
            element,
            NodeFilter.SHOW_TEXT | NodeFilter.SHOW_ELEMENT,
            {
                acceptNode: function(node) {
                    if (node.nodeType === Node.TEXT_NODE && node.textContent.trim()) {
                        return NodeFilter.FILTER_ACCEPT;
                    }
                    if (node.nodeName === 'BR') {
                        return NodeFilter.FILTER_ACCEPT;
                    }
                    return NodeFilter.FILTER_SKIP;
                }
            }
        );
        
        let node;
        while (node = walker.nextNode()) {
            nodes.push(node);
        }
        
        return nodes;
    }
    
    // 智能分词函数（中英文兼容）
    splitTextByWords(text) {
        // 对于中文，按字符分割；对于英文，按单词分割
        const words = [];
        let currentWord = '';
        
        for (let i = 0; i < text.length; i++) {
            const char = text[i];
            
            // 如果是中文字符
            if (this.isChinese(char)) {
                if (currentWord) {
                    words.push(currentWord);
                    currentWord = '';
                }
                words.push(char);
            }
            // 如果是空格或标点
            else if (/[\s\.,!?;:，。！？；：]/.test(char)) {
                if (currentWord) {
                    words.push(currentWord);
                    currentWord = '';
                }
                words.push(char);
            }
            // 如果是英文字母或数字
            else {
                currentWord += char;
            }
        }
        
        // 添加最后一个词
        if (currentWord) {
            words.push(currentWord);
        }
        
        return words;
    }
    
    // 检查是否是中文字符
    isChinese(char) {
        return /[\u4e00-\u9fff]/.test(char);
    }
    
    // 延迟函数
    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
    
    // 创建助手消息元素
    createAssistantMessage(meta = {}) {
        const chatHistory = this.elements.chatHistory;
        if (!chatHistory) return null;
        
        const messageDiv = document.createElement('div');
        messageDiv.className = 'chat-message assistant-message';
        
        if (meta.isPsychology) {
            messageDiv.classList.add('psychology-alert');
        }
        
        const icon = meta.isPsychology ? '<img src="/resources/ai_avatar.jpeg" alt="AI" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">' : '🤖';
        
        const messageContent = document.createElement('div');
        messageContent.className = 'message-content';
        messageContent.innerHTML = `
            <span class="assistant-icon">${icon}</span>
            <div class="message-text"></div>
        `;
        
        messageDiv.appendChild(messageContent);
        chatHistory.appendChild(messageDiv);
        
        // 滚动到底部
        this.scrollToBottom();
        
        return messageDiv;
    }
    
    // 为助手消息添加额外功能（按钮等）
    addAssistantMessageFeatures(messageElement, meta = {}) {
        // 清除之前的按钮
        this.clearActionButtons();
        
        const buttons = [];
        
        // 添加链接按钮（如果有）- Telegram 风格
        if (meta.links && meta.links.length > 0) {
            // 在消息下方添加链接按钮
            this.addLinkButtons(messageElement, meta.links);
        }
        
        // 如果是心理咨询相关的助手回复，显示AI接管按钮
        if (meta.isPsychology && !meta.isAI) {
            this.showActionButtons([{
                type: 'ai-handover',
                text: this.appState.currentLanguage === 'zh' ? '转交AI助手' : 'Switch to AI Assistant',
                icon: '🤖',
                action: () => this.switchToAI(meta.category || 'psychology')
            }]);
        }
        
        // 如果是AI回复，显示AI操作按钮
        if (meta.isAI) {
            messageElement.setAttribute('data-ai', 'true');
            
            // 添加退出AI模式按钮
            buttons.push({
                type: 'ai-exit',
                text: this.appState.currentLanguage === 'zh' ? '退出AI助手' : 'Exit AI Assistant',
                icon: '↩️',
                action: () => this.exitAIMode()
            });
            
            this.showActionButtons(buttons);
        }
    }
    
    // 添加链接按钮到消息内部（Telegram 风格）
    addLinkButtons(messageElement, links) {
        if (!links || links.length === 0) return;
        
        // 查找或创建链接按钮容器
        let linksContainer = messageElement.querySelector('.message-links');
        if (!linksContainer) {
            linksContainer = document.createElement('div');
            linksContainer.className = 'message-links';
            messageElement.appendChild(linksContainer);
        }
        
        // 清空现有链接
        linksContainer.innerHTML = '';
        
        // 添加每个链接按钮
        links.forEach(link => {
            const linkButton = document.createElement('a');
            linkButton.className = 'message-link-button';
            linkButton.href = link.url;
            linkButton.target = '_blank';
            linkButton.rel = 'noopener noreferrer';
            linkButton.innerHTML = `
                <span class="link-icon">🔗</span>
                <span class="link-text">${this.escapeHtml(link.text || link.title || 'Link')}</span>
                <span class="link-arrow">→</span>
            `;
            linksContainer.appendChild(linkButton);
        });
    }
    
    // 显示操作按钮
    showActionButtons(buttons) {
        const actionSection = document.getElementById('action-buttons-section');
        if (!actionSection) return;
        
        // 清空现有内容
        actionSection.innerHTML = '';
        
        // 创建容器
        const container = document.createElement('div');
        container.className = 'action-buttons-container';
        
        // 分组按钮
        const linkButtons = buttons.filter(btn => btn.type === 'ai-link');
        const otherButtons = buttons.filter(btn => btn.type !== 'ai-link');
        
        // 添加链接按钮行
        if (linkButtons.length > 0) {
            const linksRow = document.createElement('div');
            linksRow.className = 'action-buttons-row';
            
            linkButtons.forEach(btn => {
                const button = this.createActionButton(btn);
                linksRow.appendChild(button);
            });
            
            container.appendChild(linksRow);
        }
        
        // 添加其他按钮行
        if (otherButtons.length > 0) {
            const otherRow = document.createElement('div');
            otherRow.className = 'action-buttons-row';
            
            otherButtons.forEach(btn => {
                const button = this.createActionButton(btn);
                otherRow.appendChild(button);
            });
            
            container.appendChild(otherRow);
        }
        
        actionSection.appendChild(container);
        actionSection.style.display = 'block';
    }
    
    // 创建操作按钮
    createActionButton(buttonConfig) {
        const button = document.createElement('button');
        button.className = `action-button ${buttonConfig.type}`;
        button.innerHTML = `
            <span class="action-button-icon">${buttonConfig.icon}</span>
            <span class="action-button-text">${buttonConfig.text}</span>
        `;
        button.onclick = buttonConfig.action;
        button.setAttribute('title', buttonConfig.text);
        return button;
    }
    
    // 清除操作按钮
    clearActionButtons() {
        const actionSection = document.getElementById('action-buttons-section');
        if (actionSection) {
            actionSection.style.display = 'none';
            actionSection.innerHTML = '';
        }
    }
    
    // 添加时间戳到消息

    
    // 保存消息到历史记录
    saveMessageToHistory(type, content, meta = {}) {
        this.appState.chatHistory.push({
            type,
            content,
            timestamp: new Date().toISOString(),
            meta
        });
        
        // 更新清空按钮状态
        this.updateClearButtonVisibility();
    }
    
    scrollToBottom() {
        const chatHistory = this.elements.chatHistory;
        if (chatHistory) {
            setTimeout(() => {
                chatHistory.scrollTop = chatHistory.scrollHeight;
            }, 100);
        }
    }
    
    setLoading(isLoading) {
        this.appState.isLoading = isLoading;
        
        // 不再使用全屏加载覆盖层
        // if (this.elements.loadingOverlay) {
        //     this.elements.loadingOverlay.classList.toggle('active', isLoading);
        // }
        
        if (this.elements.submitBtn) {
            this.elements.submitBtn.disabled = isLoading;
        }
        
        this.updateSubmitButton();
    }
    
    updateConnectionStatus(isConnected, statusText) {
        this.appState.isConnected = isConnected;
        
        if (this.elements.statusDot) {
            this.elements.statusDot.className = `status-dot ${isConnected ? '' : 'error'}`;
        }
        
        if (this.elements.statusText) {
            this.elements.statusText.textContent = statusText;
        }
    }
    
    showError(message, duration = 5000) {
        const toast = this.elements.errorToast;
        const messageEl = document.getElementById('toast-message');
        
        if (toast && messageEl) {
            messageEl.textContent = message;
            toast.classList.add('active');
            
            // 自动隐藏
            setTimeout(() => this.hideError(), duration);
        }
    }
    
    hideError() {
        if (this.elements.errorToast) {
            this.elements.errorToast.classList.remove('active');
        }
    }
    
    async showPsychologyModal() {
        if (this.elements.psychologyModal) {
            // 从系统设置获取最新的联系方式
            try {
                const response = await fetch('./api/admin-config.php?action=get_settings&category=psychology,contact');
                const data = await response.json();
                
                if (data.success && data.settings) {
                    this.updatePsychologyModalContent(data.settings);
                }
            } catch (error) {
                console.warn('Failed to load psychology settings:', error);
            }
            
            this.elements.psychologyModal.classList.add('active');
        }
    }
    
    updatePsychologyModalContent(settings) {
        // 将设置转换为对象
        const settingsObj = {};
        settings.forEach(setting => {
            settingsObj[setting.setting_key] = setting.setting_value;
        });
        
        // 获取电话号码
        const campusCounseling = settingsObj['campus_counseling'] || '1300 653 007';
        const emergencyHotline = settingsObj['emergency_hotline'] || '4921 6622';
        
        // 更新模态框中的联系方式
        const supportContacts = this.elements.psychologyModal.querySelector('.support-contacts');
        if (supportContacts) {
            const isZh = this.appState.currentLanguage === 'zh';
            
            supportContacts.innerHTML = `
                <div class="contact-item">
                    <strong>${isZh ? '24小时危机热线:' : '24/7 Crisis Hotline:'}</strong>
                    <a href="tel:${emergencyHotline}">${emergencyHotline}</a>
                </div>
                <div class="contact-item">
                    <strong>${isZh ? '校园心理咨询:' : 'Campus Counseling:'}</strong>
                    <a href="tel:${campusCounseling}">${campusCounseling}</a>
                </div>
            `;
        }
        
        // 更新标题和消息文本
        const modalTitle = this.elements.psychologyModal.querySelector('#modal-title');
        const modalMessage = this.elements.psychologyModal.querySelector('#modal-message');
        const modalButton = this.elements.psychologyModal.querySelector('#modal-understand');
        
        if (this.appState.currentLanguage === 'zh') {
            if (modalTitle) modalTitle.textContent = '心理健康支持';
            if (modalMessage) modalMessage.textContent = '我们注意到您可能需要心理健康支持。请记住，寻求帮助是力量的表现，不是软弱。';
            if (modalButton) modalButton.textContent = '我明白了';
        } else {
            if (modalTitle) modalTitle.textContent = 'Mental Health Support';
            if (modalMessage) modalMessage.textContent = 'We noticed you might need mental health support. Please remember that seeking help is a sign of strength, not weakness.';
            if (modalButton) modalButton.textContent = 'I Understand';
        }
    }
    
    hideModal() {
        if (this.elements.psychologyModal) {
            this.elements.psychologyModal.classList.remove('active');
        }
    }
    
    clearChat() {
        // 确认对话框
        const confirmMessage = this.appState.currentLanguage === 'zh' ? 
            '确定要清空所有聊天记录吗？此操作不可撤销。' : 
            'Are you sure you want to clear all chat history? This action cannot be undone.';
            
        if (!confirm(confirmMessage)) {
            return;
        }
        
        // 清空聊天历史数组
        this.appState.chatHistory = [];
        
        // 清空DOM中的聊天记录
        const chatHistory = this.elements.chatHistory;
        if (chatHistory) {
            chatHistory.innerHTML = '';
            
            // 重新添加系统欢迎消息
            const systemMessage = document.createElement('div');
            systemMessage.className = 'chat-message system-message';
            systemMessage.innerHTML = `
                <div class="message-content">
                    <span class="system-icon">🤖</span>
                    <span id="system-greeting">${this.i18n.t('system-greeting')}</span>
                </div>
            `;
            chatHistory.appendChild(systemMessage);
        }
        
        // 清除操作按钮
        this.clearActionButtons();
        
        // 重置问题计数
        this.appState.questionCount = 0;
        this.i18n.updateQuestionCount();
        
        // 保存清空后的状态到本地存储
        this.appState.saveToStorage();
        
        // 更新清空按钮状态
        this.updateClearButtonVisibility();
        
        // 显示清空成功提示
        const successMessage = this.appState.currentLanguage === 'zh' ? 
            '聊天记录已清空' : 
            'Chat history cleared';
        this.showError(successMessage, 2000); // 使用showError来显示成功消息，2秒后消失
    }
    
    updateClearButtonVisibility() {
        const clearBtn = this.elements.clearChatBtn;
        if (clearBtn) {
            // 检查是否有用户消息或助手回复（排除系统消息）
            const hasUserMessages = this.appState.chatHistory.some(msg => 
                msg.type === 'user' || msg.type === 'assistant'
            );
            
            if (hasUserMessages) {
                clearBtn.classList.add('active');
            } else {
                clearBtn.classList.remove('active');
            }
        }
    }
    

    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    

    
    // 恢复聊天历史
    restoreChatHistory() {
        const chatHistory = this.elements.chatHistory;
        if (!chatHistory) return;
        
        // 清除除了系统欢迎消息外的所有消息
        const systemMessage = chatHistory.querySelector('.system-message');
        chatHistory.innerHTML = '';
        if (systemMessage) {
            chatHistory.appendChild(systemMessage);
        }
        
        // 恢复历史消息（不重复保存到历史数组）
        this.appState.chatHistory.forEach((msg, index) => {
            this.addMessage(msg.type, msg.content, msg.meta || {}, true);
            
            // 如果是最后一条助手消息，恢复相应的操作按钮
            if (index === this.appState.chatHistory.length - 1 && msg.type === 'assistant') {
                setTimeout(() => {
                    const messageElement = chatHistory.lastElementChild;
                    if (messageElement) {
                        this.addAssistantMessageFeatures(messageElement, msg.meta || {});
                    }
                }, 100);
            }
        });
        
        // 更新清空按钮状态
        this.updateClearButtonVisibility();
    }
    
    // AI接管功能
    switchToAI(category = 'general') {
        // 显示AI接管消息
        const takoverMessage = this.appState.currentLanguage === 'zh' ? 
            'AI助手已接管对话。我会为您提供更详细的帮助。请重新描述您的问题。' : 
            'AI Assistant has taken over the conversation. I\'ll provide you with more detailed assistance. Please describe your question again.';
        
        this.addMessage('system', takoverMessage, { isAI: true, category: category });
        
        // 设置AI模式标志
        this.appState.isAIMode = true;
        this.appState.aiCategory = category;
        
        // 更新输入框提示
        const inputPlaceholder = this.appState.currentLanguage === 'zh' ? 
            '请详细描述您的问题，AI助手会为您提供专业建议...' : 
            'Please describe your question in detail, AI assistant will provide professional advice...';
        
        if (this.elements.input) {
            this.elements.input.placeholder = inputPlaceholder;
        }
        
        // 显示AI模式指示器
        this.showAIModeIndicator();
        
        // 保存状态
        this.appState.saveToStorage();
    }
    
    showAIModeIndicator() {
        const statusSection = document.querySelector('.status-section');
        if (statusSection) {
            // 移除旧的AI指示器
            const oldIndicator = statusSection.querySelector('.ai-mode-indicator');
            if (oldIndicator) {
                oldIndicator.remove();
            }
            
            // 创建AI模式指示器（仅显示状态，不包含退出按钮）
            const aiIndicator = document.createElement('div');
            aiIndicator.className = 'ai-mode-indicator';
            
            const indicatorText = this.appState.currentLanguage === 'zh' ? 
                '🤖 AI助手模式' : '🤖 AI Assistant Mode';
            
            aiIndicator.innerHTML = `
                <span class="ai-mode-text">${indicatorText}</span>
            `;
            
            statusSection.appendChild(aiIndicator);
        }
    }
    
    exitAIMode() {
        // 退出AI模式
        this.appState.isAIMode = false;
        this.appState.aiCategory = null;
        
        // 恢复原始输入框提示
        const originalPlaceholder = this.i18n.t('input-placeholder');
        if (this.elements.input) {
            this.elements.input.placeholder = originalPlaceholder;
        }
        
        // 移除AI模式指示器
        const aiIndicator = document.querySelector('.ai-mode-indicator');
        if (aiIndicator) {
            aiIndicator.remove();
        }
        
        // 清除操作按钮
        this.clearActionButtons();
        
        // 显示退出消息
        const exitMessage = this.appState.currentLanguage === 'zh' ? 
            '已退出AI模式，回到标准问答模式。' : 
            'Exited AI mode, back to standard Q&A mode.';
        
        this.addMessage('system', exitMessage);
        
        // 保存状态
        this.appState.saveToStorage();
    }
}

// 应用程序主类
class StudentQAApp {
    constructor() {
        this.state = new AppState();
        this.i18n = new I18nManager(this.state);
        this.api = new APIManager(this.state, this.i18n);
        this.ui = new UIManager(this.state, this.i18n, this.api);
        
        // 将 UI Manager 引用传递给 I18n Manager
        this.i18n.uiManager = this.ui;
        
        this.init();
    }
    
    async loadWelcomeText() {
        try {
            const response = await fetch('api/questions.php?action=public_settings&category=general');
            const data = await response.json();
            if (data.success) {
                const settings = data.data;
                const welcomeZh = settings.find(s => s.setting_key === 'welcome_text_zh');
                const welcomeEn = settings.find(s => s.setting_key === 'welcome_text_en');
                
                if (welcomeZh) {
                    LANGUAGES.zh.translations['welcome-text'] = welcomeZh.setting_value;
                }
                if (welcomeEn) {
                    LANGUAGES.en.translations['welcome-text'] = welcomeEn.setting_value;
                }
            }
        } catch (error) {
            console.error('Failed to load welcome text:', error);
        }
    }
    
    async init() {
        // 加载欢迎文字
        await this.loadWelcomeText();
        
        // 初始化UI语言
        this.i18n.updateUI();
        
        // 检查学生ID验证状态
        if (this.state.isStudentVerified && this.state.studentId) {
            // 已验证，显示问答界面
            this.ui.elements.qaSection.style.display = 'block';
            this.ui.elements.currentStudentId.textContent = `C${this.state.studentId}`;
            this.ui.updateSystemGreeting();
            
            // 恢复聊天历史
            this.ui.restoreChatHistory();
        } else {
            // 未验证，显示学生ID对话框
            this.ui.showStudentIdDialog();
        }
        
        // 如果处于AI模式，显示AI模式指示器
        if (this.state.isAIMode) {
            this.ui.showAIModeIndicator();
            
            // 更新输入框提示
            const inputPlaceholder = this.state.currentLanguage === 'zh' ? 
                '请详细描述您的问题，AI助手会为您提供专业建议...' : 
                'Please describe your question in detail, AI assistant will provide professional advice...';
            
            const inputElement = document.getElementById('question-input');
            if (inputElement) {
                inputElement.placeholder = inputPlaceholder;
            }
        }
        
        // 测试连接
        await this.testConnection();
        
        // 设置定期连接检查
        setInterval(() => this.testConnection(), 30000); // 30秒检查一次
        
        // 添加页面卸载时保存状态
        window.addEventListener('beforeunload', () => {
            this.state.saveToStorage();
        });
        
        console.log('Student Q&A System initialized with UTF-8 support');
    }
    
    async testConnection() {
        try {
            await this.api.testConnection();
            this.ui.updateConnectionStatus(true, this.i18n.t('status-ready'));
        } catch (error) {
            console.warn('Connection test failed:', error);
            this.ui.updateConnectionStatus(false, this.i18n.t('status-error'));
        }
    }
}

// 应用程序启动
document.addEventListener('DOMContentLoaded', () => {
    // 确保UTF-8编码
    document.charset = 'UTF-8';
    
    // 启动应用
    window.qaApp = new StudentQAApp();
});

// 全局错误处理
window.addEventListener('error', (e) => {
    console.error('Global error:', e.error);
    if (window.qaApp && window.qaApp.ui) {
        window.qaApp.ui.showError('An unexpected error occurred. Please refresh the page.');
    }
});

// 全局未处理的Promise错误
window.addEventListener('unhandledrejection', (e) => {
    console.error('Unhandled promise rejection:', e.reason);
    if (window.qaApp && window.qaApp.ui) {
        window.qaApp.ui.showError('A network error occurred. Please try again.');
    }
});

// 导出供其他脚本使用
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { StudentQAApp, LANGUAGES };
} 