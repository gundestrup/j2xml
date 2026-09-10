/* @jsonjoy.com/base64 18.30.0 decode-only bundle (built from npm) */
var base64 = (() => {
  var __create = Object.create;
  var __defProp = Object.defineProperty;
  var __getOwnPropDesc = Object.getOwnPropertyDescriptor;
  var __getOwnPropNames = Object.getOwnPropertyNames;
  var __getProtoOf = Object.getPrototypeOf;
  var __hasOwnProp = Object.prototype.hasOwnProperty;
  var __esm = (fn, res, err) => function __init() {
    if (err) throw err[0];
    try {
      return fn && (res = (0, fn[__getOwnPropNames(fn)[0]])(fn = 0)), res;
    } catch (e) {
      throw err = [e], e;
    }
  };
  var __commonJS = (cb, mod) => function __require() {
    try {
      return mod || (0, cb[__getOwnPropNames(cb)[0]])((mod = { exports: {} }).exports, mod), mod.exports;
    } catch (e) {
      throw mod = 0, e;
    }
  };
  var __export = (target, all) => {
    for (var name in all)
      __defProp(target, name, { get: all[name], enumerable: true });
  };
  var __copyProps = (to, from, except, desc) => {
    if (from && typeof from === "object" || typeof from === "function") {
      for (let key of __getOwnPropNames(from))
        if (!__hasOwnProp.call(to, key) && key !== except)
          __defProp(to, key, { get: () => from[key], enumerable: !(desc = __getOwnPropDesc(from, key)) || desc.enumerable });
    }
    return to;
  };
  var __toESM = (mod, isNodeMode, target) => (target = mod != null ? __create(__getProtoOf(mod)) : {}, __copyProps(
    // If the importer is in node compatibility mode or this is not an ESM
    // file that has been converted to a CommonJS file using a Babel-
    // compatible transform (i.e. "__esModule" has not been set), then set
    // "default" to the CommonJS "module.exports" for node compatibility.
    isNodeMode || !mod || !mod.__esModule ? __defProp(target, "default", { value: mod, enumerable: true }) : target,
    mod
  ));
  var __toCommonJS = (mod) => __copyProps(__defProp({}, "__esModule", { value: true }), mod);

  // node_modules/tslib/tslib.es6.mjs
  var tslib_es6_exports = {};
  __export(tslib_es6_exports, {
    __addDisposableResource: () => __addDisposableResource,
    __assign: () => __assign,
    __asyncDelegator: () => __asyncDelegator,
    __asyncGenerator: () => __asyncGenerator,
    __asyncValues: () => __asyncValues,
    __await: () => __await,
    __awaiter: () => __awaiter,
    __classPrivateFieldGet: () => __classPrivateFieldGet,
    __classPrivateFieldIn: () => __classPrivateFieldIn,
    __classPrivateFieldSet: () => __classPrivateFieldSet,
    __createBinding: () => __createBinding,
    __decorate: () => __decorate,
    __disposeResources: () => __disposeResources,
    __esDecorate: () => __esDecorate,
    __exportStar: () => __exportStar,
    __extends: () => __extends,
    __generator: () => __generator,
    __importDefault: () => __importDefault,
    __importStar: () => __importStar,
    __makeTemplateObject: () => __makeTemplateObject,
    __metadata: () => __metadata,
    __param: () => __param,
    __propKey: () => __propKey,
    __read: () => __read,
    __rest: () => __rest,
    __rewriteRelativeImportExtension: () => __rewriteRelativeImportExtension,
    __runInitializers: () => __runInitializers,
    __setFunctionName: () => __setFunctionName,
    __spread: () => __spread,
    __spreadArray: () => __spreadArray,
    __spreadArrays: () => __spreadArrays,
    __values: () => __values,
    default: () => tslib_es6_default
  });
  function __extends(d, b) {
    if (typeof b !== "function" && b !== null)
      throw new TypeError("Class extends value " + String(b) + " is not a constructor or null");
    extendStatics(d, b);
    function __() {
      this.constructor = d;
    }
    d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
  }
  function __rest(s, e) {
    var t = {};
    for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p) && e.indexOf(p) < 0)
      t[p] = s[p];
    if (s != null && typeof Object.getOwnPropertySymbols === "function")
      for (var i = 0, p = Object.getOwnPropertySymbols(s); i < p.length; i++) {
        if (e.indexOf(p[i]) < 0 && Object.prototype.propertyIsEnumerable.call(s, p[i]))
          t[p[i]] = s[p[i]];
      }
    return t;
  }
  function __decorate(decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
  }
  function __param(paramIndex, decorator) {
    return function(target, key) {
      decorator(target, key, paramIndex);
    };
  }
  function __esDecorate(ctor, descriptorIn, decorators, contextIn, initializers, extraInitializers) {
    function accept(f) {
      if (f !== void 0 && typeof f !== "function") throw new TypeError("Function expected");
      return f;
    }
    var kind = contextIn.kind, key = kind === "getter" ? "get" : kind === "setter" ? "set" : "value";
    var target = !descriptorIn && ctor ? contextIn["static"] ? ctor : ctor.prototype : null;
    var descriptor = descriptorIn || (target ? Object.getOwnPropertyDescriptor(target, contextIn.name) : {});
    var _, done = false;
    for (var i = decorators.length - 1; i >= 0; i--) {
      var context = {};
      for (var p in contextIn) context[p] = p === "access" ? {} : contextIn[p];
      for (var p in contextIn.access) context.access[p] = contextIn.access[p];
      context.addInitializer = function(f) {
        if (done) throw new TypeError("Cannot add initializers after decoration has completed");
        extraInitializers.push(accept(f || null));
      };
      var result = (0, decorators[i])(kind === "accessor" ? { get: descriptor.get, set: descriptor.set } : descriptor[key], context);
      if (kind === "accessor") {
        if (result === void 0) continue;
        if (result === null || typeof result !== "object") throw new TypeError("Object expected");
        if (_ = accept(result.get)) descriptor.get = _;
        if (_ = accept(result.set)) descriptor.set = _;
        if (_ = accept(result.init)) initializers.unshift(_);
      } else if (_ = accept(result)) {
        if (kind === "field") initializers.unshift(_);
        else descriptor[key] = _;
      }
    }
    if (target) Object.defineProperty(target, contextIn.name, descriptor);
    done = true;
  }
  function __runInitializers(thisArg, initializers, value) {
    var useValue = arguments.length > 2;
    for (var i = 0; i < initializers.length; i++) {
      value = useValue ? initializers[i].call(thisArg, value) : initializers[i].call(thisArg);
    }
    return useValue ? value : void 0;
  }
  function __propKey(x) {
    return typeof x === "symbol" ? x : "".concat(x);
  }
  function __setFunctionName(f, name, prefix) {
    if (typeof name === "symbol") name = name.description ? "[".concat(name.description, "]") : "";
    return Object.defineProperty(f, "name", { configurable: true, value: prefix ? "".concat(prefix, " ", name) : name });
  }
  function __metadata(metadataKey, metadataValue) {
    if (typeof Reflect === "object" && typeof Reflect.metadata === "function") return Reflect.metadata(metadataKey, metadataValue);
  }
  function __awaiter(thisArg, _arguments, P, generator) {
    function adopt(value) {
      return value instanceof P ? value : new P(function(resolve) {
        resolve(value);
      });
    }
    return new (P || (P = Promise))(function(resolve, reject) {
      function fulfilled(value) {
        try {
          step(generator.next(value));
        } catch (e) {
          reject(e);
        }
      }
      function rejected(value) {
        try {
          step(generator["throw"](value));
        } catch (e) {
          reject(e);
        }
      }
      function step(result) {
        result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected);
      }
      step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
  }
  function __generator(thisArg, body) {
    var _ = { label: 0, sent: function() {
      if (t[0] & 1) throw t[1];
      return t[1];
    }, trys: [], ops: [] }, f, y, t, g = Object.create((typeof Iterator === "function" ? Iterator : Object).prototype);
    return g.next = verb(0), g["throw"] = verb(1), g["return"] = verb(2), typeof Symbol === "function" && (g[Symbol.iterator] = function() {
      return this;
    }), g;
    function verb(n) {
      return function(v) {
        return step([n, v]);
      };
    }
    function step(op) {
      if (f) throw new TypeError("Generator is already executing.");
      while (g && (g = 0, op[0] && (_ = 0)), _) try {
        if (f = 1, y && (t = op[0] & 2 ? y["return"] : op[0] ? y["throw"] || ((t = y["return"]) && t.call(y), 0) : y.next) && !(t = t.call(y, op[1])).done) return t;
        if (y = 0, t) op = [op[0] & 2, t.value];
        switch (op[0]) {
          case 0:
          case 1:
            t = op;
            break;
          case 4:
            _.label++;
            return { value: op[1], done: false };
          case 5:
            _.label++;
            y = op[1];
            op = [0];
            continue;
          case 7:
            op = _.ops.pop();
            _.trys.pop();
            continue;
          default:
            if (!(t = _.trys, t = t.length > 0 && t[t.length - 1]) && (op[0] === 6 || op[0] === 2)) {
              _ = 0;
              continue;
            }
            if (op[0] === 3 && (!t || op[1] > t[0] && op[1] < t[3])) {
              _.label = op[1];
              break;
            }
            if (op[0] === 6 && _.label < t[1]) {
              _.label = t[1];
              t = op;
              break;
            }
            if (t && _.label < t[2]) {
              _.label = t[2];
              _.ops.push(op);
              break;
            }
            if (t[2]) _.ops.pop();
            _.trys.pop();
            continue;
        }
        op = body.call(thisArg, _);
      } catch (e) {
        op = [6, e];
        y = 0;
      } finally {
        f = t = 0;
      }
      if (op[0] & 5) throw op[1];
      return { value: op[0] ? op[1] : void 0, done: true };
    }
  }
  function __exportStar(m, o) {
    for (var p in m) if (p !== "default" && !Object.prototype.hasOwnProperty.call(o, p)) __createBinding(o, m, p);
  }
  function __values(o) {
    var s = typeof Symbol === "function" && Symbol.iterator, m = s && o[s], i = 0;
    if (m) return m.call(o);
    if (o && typeof o.length === "number") return {
      next: function() {
        if (o && i >= o.length) o = void 0;
        return { value: o && o[i++], done: !o };
      }
    };
    throw new TypeError(s ? "Object is not iterable." : "Symbol.iterator is not defined.");
  }
  function __read(o, n) {
    var m = typeof Symbol === "function" && o[Symbol.iterator];
    if (!m) return o;
    var i = m.call(o), r, ar = [], e;
    try {
      while ((n === void 0 || n-- > 0) && !(r = i.next()).done) ar.push(r.value);
    } catch (error) {
      e = { error };
    } finally {
      try {
        if (r && !r.done && (m = i["return"])) m.call(i);
      } finally {
        if (e) throw e.error;
      }
    }
    return ar;
  }
  function __spread() {
    for (var ar = [], i = 0; i < arguments.length; i++)
      ar = ar.concat(__read(arguments[i]));
    return ar;
  }
  function __spreadArrays() {
    for (var s = 0, i = 0, il = arguments.length; i < il; i++) s += arguments[i].length;
    for (var r = Array(s), k = 0, i = 0; i < il; i++)
      for (var a = arguments[i], j = 0, jl = a.length; j < jl; j++, k++)
        r[k] = a[j];
    return r;
  }
  function __spreadArray(to, from, pack) {
    if (pack || arguments.length === 2) for (var i = 0, l = from.length, ar; i < l; i++) {
      if (ar || !(i in from)) {
        if (!ar) ar = Array.prototype.slice.call(from, 0, i);
        ar[i] = from[i];
      }
    }
    return to.concat(ar || Array.prototype.slice.call(from));
  }
  function __await(v) {
    return this instanceof __await ? (this.v = v, this) : new __await(v);
  }
  function __asyncGenerator(thisArg, _arguments, generator) {
    if (!Symbol.asyncIterator) throw new TypeError("Symbol.asyncIterator is not defined.");
    var g = generator.apply(thisArg, _arguments || []), i, q = [];
    return i = Object.create((typeof AsyncIterator === "function" ? AsyncIterator : Object).prototype), verb("next"), verb("throw"), verb("return", awaitReturn), i[Symbol.asyncIterator] = function() {
      return this;
    }, i;
    function awaitReturn(f) {
      return function(v) {
        return Promise.resolve(v).then(f, reject);
      };
    }
    function verb(n, f) {
      if (g[n]) {
        i[n] = function(v) {
          return new Promise(function(a, b) {
            q.push([n, v, a, b]) > 1 || resume(n, v);
          });
        };
        if (f) i[n] = f(i[n]);
      }
    }
    function resume(n, v) {
      try {
        step(g[n](v));
      } catch (e) {
        settle(q[0][3], e);
      }
    }
    function step(r) {
      r.value instanceof __await ? Promise.resolve(r.value.v).then(fulfill, reject) : settle(q[0][2], r);
    }
    function fulfill(value) {
      resume("next", value);
    }
    function reject(value) {
      resume("throw", value);
    }
    function settle(f, v) {
      if (f(v), q.shift(), q.length) resume(q[0][0], q[0][1]);
    }
  }
  function __asyncDelegator(o) {
    var i, p;
    return i = {}, verb("next"), verb("throw", function(e) {
      throw e;
    }), verb("return"), i[Symbol.iterator] = function() {
      return this;
    }, i;
    function verb(n, f) {
      i[n] = o[n] ? function(v) {
        return (p = !p) ? { value: __await(o[n](v)), done: false } : f ? f(v) : v;
      } : f;
    }
  }
  function __asyncValues(o) {
    if (!Symbol.asyncIterator) throw new TypeError("Symbol.asyncIterator is not defined.");
    var m = o[Symbol.asyncIterator], i;
    return m ? m.call(o) : (o = typeof __values === "function" ? __values(o) : o[Symbol.iterator](), i = {}, verb("next"), verb("throw"), verb("return"), i[Symbol.asyncIterator] = function() {
      return this;
    }, i);
    function verb(n) {
      i[n] = o[n] && function(v) {
        return new Promise(function(resolve, reject) {
          v = o[n](v), settle(resolve, reject, v.done, v.value);
        });
      };
    }
    function settle(resolve, reject, d, v) {
      Promise.resolve(v).then(function(v2) {
        resolve({ value: v2, done: d });
      }, reject);
    }
  }
  function __makeTemplateObject(cooked, raw) {
    if (Object.defineProperty) {
      Object.defineProperty(cooked, "raw", { value: raw });
    } else {
      cooked.raw = raw;
    }
    return cooked;
  }
  function __importStar(mod) {
    if (mod && mod.__esModule) return mod;
    var result = {};
    if (mod != null) {
      for (var k = ownKeys(mod), i = 0; i < k.length; i++) if (k[i] !== "default") __createBinding(result, mod, k[i]);
    }
    __setModuleDefault(result, mod);
    return result;
  }
  function __importDefault(mod) {
    return mod && mod.__esModule ? mod : { default: mod };
  }
  function __classPrivateFieldGet(receiver, state, kind, f) {
    if (kind === "a" && !f) throw new TypeError("Private accessor was defined without a getter");
    if (typeof state === "function" ? receiver !== state || !f : !state.has(receiver)) throw new TypeError("Cannot read private member from an object whose class did not declare it");
    return kind === "m" ? f : kind === "a" ? f.call(receiver) : f ? f.value : state.get(receiver);
  }
  function __classPrivateFieldSet(receiver, state, value, kind, f) {
    if (kind === "m") throw new TypeError("Private method is not writable");
    if (kind === "a" && !f) throw new TypeError("Private accessor was defined without a setter");
    if (typeof state === "function" ? receiver !== state || !f : !state.has(receiver)) throw new TypeError("Cannot write private member to an object whose class did not declare it");
    return kind === "a" ? f.call(receiver, value) : f ? f.value = value : state.set(receiver, value), value;
  }
  function __classPrivateFieldIn(state, receiver) {
    if (receiver === null || typeof receiver !== "object" && typeof receiver !== "function") throw new TypeError("Cannot use 'in' operator on non-object");
    return typeof state === "function" ? receiver === state : state.has(receiver);
  }
  function __addDisposableResource(env, value, async) {
    if (value !== null && value !== void 0) {
      if (typeof value !== "object" && typeof value !== "function") throw new TypeError("Object expected.");
      var dispose, inner;
      if (async) {
        if (!Symbol.asyncDispose) throw new TypeError("Symbol.asyncDispose is not defined.");
        dispose = value[Symbol.asyncDispose];
      }
      if (dispose === void 0) {
        if (!Symbol.dispose) throw new TypeError("Symbol.dispose is not defined.");
        dispose = value[Symbol.dispose];
        if (async) inner = dispose;
      }
      if (typeof dispose !== "function") throw new TypeError("Object not disposable.");
      if (inner) dispose = function() {
        try {
          inner.call(this);
        } catch (e) {
          return Promise.reject(e);
        }
      };
      env.stack.push({ value, dispose, async });
    } else if (async) {
      env.stack.push({ async: true });
    }
    return value;
  }
  function __disposeResources(env) {
    function fail(e) {
      env.error = env.hasError ? new _SuppressedError(e, env.error, "An error was suppressed during disposal.") : e;
      env.hasError = true;
    }
    var r, s = 0;
    function next() {
      while (r = env.stack.pop()) {
        try {
          if (!r.async && s === 1) return s = 0, env.stack.push(r), Promise.resolve().then(next);
          if (r.dispose) {
            var result = r.dispose.call(r.value);
            if (r.async) return s |= 2, Promise.resolve(result).then(next, function(e) {
              fail(e);
              return next();
            });
          } else s |= 1;
        } catch (e) {
          fail(e);
        }
      }
      if (s === 1) return env.hasError ? Promise.reject(env.error) : Promise.resolve();
      if (env.hasError) throw env.error;
    }
    return next();
  }
  function __rewriteRelativeImportExtension(path, preserveJsx) {
    if (typeof path === "string" && /^\.\.?\//.test(path)) {
      return path.replace(/\.(tsx)$|((?:\.d)?)((?:\.[^./]+?)?)\.([cm]?)ts$/i, function(m, tsx, d, ext, cm) {
        return tsx ? preserveJsx ? ".jsx" : ".js" : d && (!ext || !cm) ? m : d + ext + "." + cm.toLowerCase() + "js";
      });
    }
    return path;
  }
  var extendStatics, __assign, __createBinding, __setModuleDefault, ownKeys, _SuppressedError, tslib_es6_default;
  var init_tslib_es6 = __esm({
    "node_modules/tslib/tslib.es6.mjs"() {
      extendStatics = function(d, b) {
        extendStatics = Object.setPrototypeOf || { __proto__: [] } instanceof Array && function(d2, b2) {
          d2.__proto__ = b2;
        } || function(d2, b2) {
          for (var p in b2) if (Object.prototype.hasOwnProperty.call(b2, p)) d2[p] = b2[p];
        };
        return extendStatics(d, b);
      };
      __assign = function() {
        __assign = Object.assign || function __assign2(t) {
          for (var s, i = 1, n = arguments.length; i < n; i++) {
            s = arguments[i];
            for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p)) t[p] = s[p];
          }
          return t;
        };
        return __assign.apply(this, arguments);
      };
      __createBinding = Object.create ? (function(o, m, k, k2) {
        if (k2 === void 0) k2 = k;
        var desc = Object.getOwnPropertyDescriptor(m, k);
        if (!desc || ("get" in desc ? !m.__esModule : desc.writable || desc.configurable)) {
          desc = { enumerable: true, get: function() {
            return m[k];
          } };
        }
        Object.defineProperty(o, k2, desc);
      }) : (function(o, m, k, k2) {
        if (k2 === void 0) k2 = k;
        o[k2] = m[k];
      });
      __setModuleDefault = Object.create ? (function(o, v) {
        Object.defineProperty(o, "default", { enumerable: true, value: v });
      }) : function(o, v) {
        o["default"] = v;
      };
      ownKeys = function(o) {
        ownKeys = Object.getOwnPropertyNames || function(o2) {
          var ar = [];
          for (var k in o2) if (Object.prototype.hasOwnProperty.call(o2, k)) ar[ar.length] = k;
          return ar;
        };
        return ownKeys(o);
      };
      _SuppressedError = typeof SuppressedError === "function" ? SuppressedError : function(error, suppressed, message) {
        var e = new Error(message);
        return e.name = "SuppressedError", e.error = error, e.suppressed = suppressed, e;
      };
      tslib_es6_default = {
        __extends,
        __assign,
        __rest,
        __decorate,
        __param,
        __esDecorate,
        __runInitializers,
        __propKey,
        __setFunctionName,
        __metadata,
        __awaiter,
        __generator,
        __createBinding,
        __exportStar,
        __values,
        __read,
        __spread,
        __spreadArrays,
        __spreadArray,
        __await,
        __asyncGenerator,
        __asyncDelegator,
        __asyncValues,
        __makeTemplateObject,
        __importStar,
        __importDefault,
        __classPrivateFieldGet,
        __classPrivateFieldSet,
        __classPrivateFieldIn,
        __addDisposableResource,
        __disposeResources,
        __rewriteRelativeImportExtension
      };
    }
  });

  // node_modules/@jsonjoy.com/base64/lib/util/strings/flatstr.js
  var require_flatstr = __commonJS({
    "node_modules/@jsonjoy.com/base64/lib/util/strings/flatstr.js"(exports) {
      "use strict";
      Object.defineProperty(exports, "__esModule", { value: true });
      exports.flatstr = void 0;
      var flatstr = (s) => {
        s | 0;
        Number(s);
        return s;
      };
      exports.flatstr = flatstr;
    }
  });

  // node_modules/@jsonjoy.com/base64/lib/constants.js
  var require_constants = __commonJS({
    "node_modules/@jsonjoy.com/base64/lib/constants.js"(exports) {
      "use strict";
      Object.defineProperty(exports, "__esModule", { value: true });
      exports.hasBuffer = exports.alphabet = void 0;
      exports.alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";
      exports.hasBuffer = typeof Buffer === "function" && typeof Buffer.from === "function";
    }
  });

  // node_modules/@jsonjoy.com/base64/lib/createToBase64.js
  var require_createToBase64 = __commonJS({
    "node_modules/@jsonjoy.com/base64/lib/createToBase64.js"(exports) {
      "use strict";
      Object.defineProperty(exports, "__esModule", { value: true });
      exports.createToBase64 = void 0;
      var flatstr_1 = require_flatstr();
      var constants_1 = require_constants();
      var createToBase64 = (chars = constants_1.alphabet, pad = "=") => {
        if (chars.length !== 64)
          throw new Error("chars must be 64 characters long");
        const table = chars.split("");
        const table2 = [];
        for (const c1 of table) {
          for (const c2 of table) {
            const two = (0, flatstr_1.flatstr)(c1 + c2);
            table2.push(two);
          }
        }
        const E = pad;
        const EE = (0, flatstr_1.flatstr)(pad + pad);
        return (uint8, length) => {
          let out = "";
          const extraLength = length % 3;
          const baseLength = length - extraLength;
          for (let i = 0; i < baseLength; i += 3) {
            const o1 = uint8[i];
            const o2 = uint8[i + 1];
            const o3 = uint8[i + 2];
            const v1 = o1 << 4 | o2 >> 4;
            const v2 = (o2 & 15) << 8 | o3;
            out += table2[v1] + table2[v2];
          }
          if (!extraLength)
            return out;
          if (extraLength === 1) {
            const o1 = uint8[baseLength];
            out += table2[o1 << 4] + EE;
          } else {
            const o1 = uint8[baseLength];
            const o2 = uint8[baseLength + 1];
            const v1 = o1 << 4 | o2 >> 4;
            const v2 = (o2 & 15) << 2;
            out += table2[v1] + table[v2] + E;
          }
          return out;
        };
      };
      exports.createToBase64 = createToBase64;
    }
  });

  // node_modules/@jsonjoy.com/base64/lib/createToBase64Bin.js
  var require_createToBase64Bin = __commonJS({
    "node_modules/@jsonjoy.com/base64/lib/createToBase64Bin.js"(exports) {
      "use strict";
      Object.defineProperty(exports, "__esModule", { value: true });
      exports.createToBase64Bin = void 0;
      var constants_1 = require_constants();
      var createToBase64Bin = (chars = constants_1.alphabet, pad = "=") => {
        if (chars.length !== 64)
          throw new Error("chars must be 64 characters long");
        const table = chars.split("").map((c) => c.charCodeAt(0));
        const table2 = [];
        for (const c1 of table) {
          for (const c2 of table) {
            const two = (c1 << 8) + c2;
            table2.push(two);
          }
        }
        const doAddPadding = pad.length === 1;
        const E = doAddPadding ? pad.charCodeAt(0) : 0;
        const EE = doAddPadding ? E << 8 | E : 0;
        return (uint8, start, length, dest, offset) => {
          const extraLength = length % 3;
          const baseLength = length - extraLength;
          for (; start < baseLength; start += 3) {
            const o1 = uint8[start];
            const o2 = uint8[start + 1];
            const o3 = uint8[start + 2];
            const v1 = o1 << 4 | o2 >> 4;
            const v2 = (o2 & 15) << 8 | o3;
            dest.setInt32(offset, (table2[v1] << 16) + table2[v2]);
            offset += 4;
          }
          if (extraLength === 1) {
            const o1 = uint8[baseLength];
            if (doAddPadding) {
              dest.setInt32(offset, (table2[o1 << 4] << 16) + EE);
              offset += 4;
            } else {
              dest.setInt16(offset, table2[o1 << 4]);
              offset += 2;
            }
          } else if (extraLength) {
            const o1 = uint8[baseLength];
            const o2 = uint8[baseLength + 1];
            const v1 = o1 << 4 | o2 >> 4;
            const v2 = (o2 & 15) << 2;
            if (doAddPadding) {
              dest.setInt32(offset, (table2[v1] << 16) + (table[v2] << 8) + E);
              offset += 4;
            } else {
              dest.setInt16(offset, table2[v1]);
              offset += 2;
              dest.setInt8(offset, table[v2]);
              offset += 1;
            }
          }
          return offset;
        };
      };
      exports.createToBase64Bin = createToBase64Bin;
    }
  });

  // node_modules/@jsonjoy.com/base64/lib/createFromBase64.js
  var require_createFromBase64 = __commonJS({
    "node_modules/@jsonjoy.com/base64/lib/createFromBase64.js"(exports) {
      "use strict";
      Object.defineProperty(exports, "__esModule", { value: true });
      exports.createFromBase64 = void 0;
      var constants_1 = require_constants();
      var E = "=";
      var createFromBase64 = (chars = constants_1.alphabet, noPadding = false) => {
        if (chars.length !== 64)
          throw new Error("chars must be 64 characters long");
        let max = 0;
        for (let i = 0; i < chars.length; i++)
          max = Math.max(max, chars.charCodeAt(i));
        const table = [];
        for (let i = 0; i <= max; i += 1)
          table[i] = -1;
        for (let i = 0; i < chars.length; i++)
          table[chars.charCodeAt(i)] = i;
        return (encoded) => {
          if (!encoded)
            return new Uint8Array(0);
          let length = encoded.length;
          if (noPadding) {
            const mod = length % 4;
            if (mod === 2) {
              encoded += "==";
              length += 2;
            } else if (mod === 3) {
              encoded += "=";
              length += 1;
            }
          }
          if (length % 4 !== 0)
            throw new Error("Base64 string length must be a multiple of 4");
          const mainLength = encoded[length - 1] !== E ? length : length - 4;
          let bufferLength = (length >> 2) * 3;
          let padding = 0;
          if (encoded[length - 2] === E) {
            padding = 2;
            bufferLength -= 2;
          } else if (encoded[length - 1] === E) {
            padding = 1;
            bufferLength -= 1;
          }
          const buf = new Uint8Array(bufferLength);
          let j = 0;
          let i = 0;
          for (; i < mainLength; i += 4) {
            const sextet0 = table[encoded.charCodeAt(i)];
            const sextet1 = table[encoded.charCodeAt(i + 1)];
            const sextet2 = table[encoded.charCodeAt(i + 2)];
            const sextet3 = table[encoded.charCodeAt(i + 3)];
            if (sextet0 < 0 || sextet1 < 0 || sextet2 < 0 || sextet3 < 0)
              throw new Error("INVALID_BASE64_STRING");
            buf[j] = sextet0 << 2 | sextet1 >> 4;
            buf[j + 1] = sextet1 << 4 | sextet2 >> 2;
            buf[j + 2] = sextet2 << 6 | sextet3;
            j += 3;
          }
          if (padding === 2) {
            const sextet0 = table[encoded.charCodeAt(mainLength)];
            const sextet1 = table[encoded.charCodeAt(mainLength + 1)];
            if (sextet0 < 0 || sextet1 < 0)
              throw new Error("INVALID_BASE64_STRING");
            buf[j] = sextet0 << 2 | sextet1 >> 4;
          } else if (padding === 1) {
            const sextet0 = table[encoded.charCodeAt(mainLength)];
            const sextet1 = table[encoded.charCodeAt(mainLength + 1)];
            const sextet2 = table[encoded.charCodeAt(mainLength + 2)];
            if (sextet0 < 0 || sextet1 < 0 || sextet2 < 0)
              throw new Error("INVALID_BASE64_STRING");
            buf[j] = sextet0 << 2 | sextet1 >> 4;
            buf[j + 1] = sextet1 << 4 | sextet2 >> 2;
          }
          return buf;
        };
      };
      exports.createFromBase64 = createFromBase64;
    }
  });

  // node_modules/@jsonjoy.com/base64/lib/toBase64.js
  var require_toBase64 = __commonJS({
    "node_modules/@jsonjoy.com/base64/lib/toBase64.js"(exports) {
      "use strict";
      Object.defineProperty(exports, "__esModule", { value: true });
      exports.toBase64 = void 0;
      var constants_1 = require_constants();
      var createToBase64_1 = require_createToBase64();
      var encodeSmall = (0, createToBase64_1.createToBase64)();
      exports.toBase64 = !constants_1.hasBuffer ? (uint8) => encodeSmall(uint8, uint8.length) : (uint8) => {
        const length = uint8.length;
        if (length <= 48)
          return encodeSmall(uint8, length);
        return Buffer.from(uint8).toString("base64");
      };
    }
  });

  // node_modules/@jsonjoy.com/base64/lib/toBase64Bin.js
  var require_toBase64Bin = __commonJS({
    "node_modules/@jsonjoy.com/base64/lib/toBase64Bin.js"(exports) {
      "use strict";
      Object.defineProperty(exports, "__esModule", { value: true });
      exports.toBase64Bin = void 0;
      var createToBase64Bin_1 = require_createToBase64Bin();
      exports.toBase64Bin = (0, createToBase64Bin_1.createToBase64Bin)();
    }
  });

  // node_modules/@jsonjoy.com/base64/lib/util/buffers/bufferToUint8Array.js
  var require_bufferToUint8Array = __commonJS({
    "node_modules/@jsonjoy.com/base64/lib/util/buffers/bufferToUint8Array.js"(exports) {
      "use strict";
      Object.defineProperty(exports, "__esModule", { value: true });
      exports.bufferToUint8Array = void 0;
      var bufferToUint8Array = (buf) => new Uint8Array(buf.buffer, buf.byteOffset, buf.length);
      exports.bufferToUint8Array = bufferToUint8Array;
    }
  });

  // node_modules/@jsonjoy.com/base64/lib/fromBase64.js
  var require_fromBase64 = __commonJS({
    "node_modules/@jsonjoy.com/base64/lib/fromBase64.js"(exports) {
      "use strict";
      Object.defineProperty(exports, "__esModule", { value: true });
      exports.fromBase64 = void 0;
      var bufferToUint8Array_1 = require_bufferToUint8Array();
      var constants_1 = require_constants();
      var createFromBase64_1 = require_createFromBase64();
      var fromBase64Cpp = constants_1.hasBuffer ? (encoded) => (0, bufferToUint8Array_1.bufferToUint8Array)(Buffer.from(encoded, "base64")) : null;
      var fromBase64Native = (0, createFromBase64_1.createFromBase64)();
      exports.fromBase64 = !fromBase64Cpp ? fromBase64Native : (encoded) => encoded.length > 48 ? fromBase64Cpp(encoded) : fromBase64Native(encoded);
    }
  });

  // node_modules/@jsonjoy.com/base64/lib/createFromBase64Bin.js
  var require_createFromBase64Bin = __commonJS({
    "node_modules/@jsonjoy.com/base64/lib/createFromBase64Bin.js"(exports) {
      "use strict";
      Object.defineProperty(exports, "__esModule", { value: true });
      exports.createFromBase64Bin = void 0;
      var constants_1 = require_constants();
      var createFromBase64Bin = (chars = constants_1.alphabet, pad = "=") => {
        if (chars.length !== 64)
          throw new Error("chars must be 64 characters long");
        let max = 0;
        for (let i = 0; i < chars.length; i++)
          max = Math.max(max, chars.charCodeAt(i));
        const table = [];
        for (let i = 0; i <= max; i += 1)
          table[i] = -1;
        for (let i = 0; i < chars.length; i++)
          table[chars.charCodeAt(i)] = i;
        const doExpectPadding = pad.length === 1;
        const PAD = doExpectPadding ? pad.charCodeAt(0) : 0;
        return (view, offset, length) => {
          if (!length)
            return new Uint8Array(0);
          let padding = 0;
          if (length % 4 !== 0) {
            padding = 4 - length % 4;
            length += padding;
          } else {
            const end = offset + length;
            const last = end - 1;
            if (view.getUint8(last) === PAD) {
              padding = 1;
              if (length > 1 && view.getUint8(last - 1) === PAD)
                padding = 2;
            }
          }
          if (length % 4 !== 0)
            throw new Error("Base64 string length must be a multiple of 4");
          const mainEnd = offset + length - (padding ? 4 : 0);
          const bufferLength = (length >> 2) * 3 - padding;
          const buf = new Uint8Array(bufferLength);
          let j = 0;
          let i = offset;
          for (; i < mainEnd; i += 4) {
            const word2 = view.getUint32(i);
            const octet02 = word2 >>> 24;
            const octet12 = word2 >>> 16 & 255;
            const octet2 = word2 >>> 8 & 255;
            const octet3 = word2 & 255;
            const sextet02 = table[octet02];
            const sextet12 = table[octet12];
            const sextet2 = table[octet2];
            const sextet3 = table[octet3];
            if (sextet02 < 0 || sextet12 < 0 || sextet2 < 0 || sextet3 < 0)
              throw new Error("INVALID_BASE64_SEQ");
            buf[j] = sextet02 << 2 | sextet12 >> 4;
            buf[j + 1] = sextet12 << 4 | sextet2 >> 2;
            buf[j + 2] = sextet2 << 6 | sextet3;
            j += 3;
          }
          if (!padding)
            return buf;
          if (padding === 1) {
            const word2 = view.getUint16(mainEnd);
            const octet02 = word2 >> 8;
            const octet12 = word2 & 255;
            const octet2 = view.getUint8(mainEnd + 2);
            const sextet02 = table[octet02];
            const sextet12 = table[octet12];
            const sextet2 = table[octet2];
            if (sextet02 < 0 || sextet12 < 0 || sextet2 < 0)
              throw new Error("INVALID_BASE64_SEQ");
            buf[j] = sextet02 << 2 | sextet12 >> 4;
            buf[j + 1] = sextet12 << 4 | sextet2 >> 2;
            return buf;
          }
          const word = view.getUint16(mainEnd);
          const octet0 = word >> 8;
          const octet1 = word & 255;
          const sextet0 = table[octet0];
          const sextet1 = table[octet1];
          if (sextet0 < 0 || sextet1 < 0)
            throw new Error("INVALID_BASE64_SEQ");
          buf[j] = sextet0 << 2 | sextet1 >> 4;
          return buf;
        };
      };
      exports.createFromBase64Bin = createFromBase64Bin;
    }
  });

  // node_modules/@jsonjoy.com/base64/lib/fromBase64Bin.js
  var require_fromBase64Bin = __commonJS({
    "node_modules/@jsonjoy.com/base64/lib/fromBase64Bin.js"(exports) {
      "use strict";
      Object.defineProperty(exports, "__esModule", { value: true });
      exports.fromBase64Bin = void 0;
      var createFromBase64Bin_1 = require_createFromBase64Bin();
      exports.fromBase64Bin = (0, createFromBase64Bin_1.createFromBase64Bin)();
    }
  });

  // node_modules/@jsonjoy.com/base64/lib/index.js
  var require_lib = __commonJS({
    "node_modules/@jsonjoy.com/base64/lib/index.js"(exports) {
      "use strict";
      Object.defineProperty(exports, "__esModule", { value: true });
      var tslib_1 = (init_tslib_es6(), __toCommonJS(tslib_es6_exports));
      tslib_1.__exportStar(require_createToBase64(), exports);
      tslib_1.__exportStar(require_createToBase64Bin(), exports);
      tslib_1.__exportStar(require_createFromBase64(), exports);
      tslib_1.__exportStar(require_toBase64(), exports);
      tslib_1.__exportStar(require_toBase64Bin(), exports);
      tslib_1.__exportStar(require_fromBase64(), exports);
      tslib_1.__exportStar(require_fromBase64Bin(), exports);
    }
  });

  // entry.js
  var entry_exports = {};
  __export(entry_exports, {
    decode: () => decode
  });
  var import_base64 = __toESM(require_lib());
  function decode(data) {
    return new TextDecoder("utf-8").decode((0, import_base64.fromBase64)(data));
  }
  return __toCommonJS(entry_exports);
})();
